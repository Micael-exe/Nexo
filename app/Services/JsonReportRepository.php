<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

/** Armazenamento privado local. O lock separado continua válido após o rename. */
class JsonReportRepository
{
    public function create(array $data, array $photos, string $submissionKey, ?callable $beforeCreate = null): array
    {
        $directory = config('canal.data_path');
        if (! is_dir($directory) && ! @mkdir($directory, 0700, true) && ! is_dir($directory)) {
            throw new RuntimeException('Não foi possível preparar o armazenamento.');
        }

        $lock = @fopen($directory.'/relatos.lock', 'c');
        if ($lock === false) {
            throw new RuntimeException('Não foi possível acessar o armazenamento.');
        }

        $createdPaths = [];
        $temporary = null;
        $committed = false;
        try {
            if (! flock($lock, LOCK_EX)) {
                throw new RuntimeException('Não foi possível obter o bloqueio de escrita.');
            }

            $file = $directory.'/relatos.json';
            $document = $this->readDocument($file);
            $keyHash = hash('sha256', $submissionKey);
            foreach ($document['relatos'] as $report) {
                if (hash_equals($report['submission_key_hash'], $keyHash)) {
                    return $this->receipt($report, false);
                }
            }

            // Limitar novos envios somente depois de procurar uma tentativa já gravada.
            // Assim uma resposta perdida continua recuperável mesmo após atingir o limite.
            if ($beforeCreate !== null) {
                $beforeCreate($data);
            }

            $id = (string) Str::uuid();
            foreach ($photos as $photo) {
                $createdPaths[] = $this->storePhoto($photo, $id);
            }
            $credential = rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
            $receivedAt = now()->utc()->toIso8601String();
            $report = [
                'id' => $id,
                'version' => 1,
                'protocol' => 'CED-'.now()->format('Ymd').'-'.strtoupper(bin2hex(random_bytes(6))),
                'submission_key_hash' => $keyHash,
                'credential_hash' => hash('sha256', $credential),
                // Necessário apenas para devolver o mesmo recibo após falha de conexão.
                'receipt_secret_encrypted' => Crypt::encryptString($credential),
                'origin' => 'web_demo',
                'is_demo' => true,
                'status' => 'Recebido',
                'received_at' => $receivedAt,
                'observed_at' => $data['observed_at'],
                'category' => $data['category'],
                'description' => $data['description'],
                'location' => [
                    'address' => $data['address'], 'reference' => $data['reference'],
                    'latitude' => $data['latitude'], 'longitude' => $data['longitude'],
                    'source' => $data['latitude'] === null ? 'address' : 'address_and_device_coordinates',
                ],
                'contact' => ['email' => $data['email'], 'phone' => $data['phone'], 'verified' => false],
                'attachments' => $createdPaths,
                'privacy_notice' => ['version' => config('canal.privacy_version'), 'acknowledged_at' => $receivedAt],
            ];
            $document['relatos'][] = $report;
            $json = json_encode($document, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            $temporary = $directory.'/.relatos-'.bin2hex(random_bytes(12)).'.tmp';
            $stream = @fopen($temporary, 'x+b');
            if ($stream === false) {
                throw new RuntimeException('Não foi possível preparar o arquivo.');
            }
            try {
                $offset = 0;
                $length = strlen($json);
                while ($offset < $length) {
                    $written = fwrite($stream, substr($json, $offset));
                    if ($written === false || $written === 0) {
                        throw new RuntimeException('Falha na escrita do arquivo.');
                    }
                    $offset += $written;
                }
                if (! fflush($stream) || (function_exists('fsync') && ! fsync($stream))) {
                    throw new RuntimeException('Falha ao confirmar a escrita.');
                }
            } finally {
                fclose($stream);
            }
            @chmod($temporary, 0600);
            if (! @rename($temporary, $file)) {
                throw new RuntimeException('Falha ao concluir a gravação.');
            }
            $temporary = null;
            $committed = true;

            return $this->receipt($report, true);
        } catch (Throwable $exception) {
            if (! $committed) {
                foreach ($createdPaths as $attachment) {
                    try { Storage::disk('local')->delete($attachment['path']); } catch (Throwable) { /* Preservar o erro original. */ }
                }
            }
            throw $exception;
        } finally {
            if ($temporary !== null && is_file($temporary)) {
                @unlink($temporary);
            }
            flock($lock, LOCK_UN);
            fclose($lock);
        }
    }

    /**
     * Consulta privada: exige protocolo E credencial. Não revela qual dos dois falhou.
     * Devolve somente a visão pública do próprio solicitante (sem contato, hashes ou caminhos).
     */
    public function findVerified(string $protocol, string $credential): ?array
    {
        $document = $this->readShared();
        $credentialHash = hash('sha256', $credential);
        $found = null;
        // Percorre todos os registros para não variar o tempo conforme a posição do acerto.
        foreach ($document['relatos'] as $report) {
            $protocolMatches = hash_equals((string) $report['protocol'], $protocol);
            $credentialMatches = hash_equals((string) ($report['credential_hash'] ?? ''), $credentialHash);
            if ($protocolMatches && $credentialMatches) {
                $found = $report;
            }
        }

        return $found === null ? null : $this->publicView($found);
    }

    /** Visão pública de relatos já autenticados nesta sessão, na ordem dos IDs recebidos. */
    public function summaries(array $ids): array
    {
        $byId = [];
        foreach ($this->readShared()['relatos'] as $report) {
            if (isset($report['id'])) {
                $byId[$report['id']] = $report;
            }
        }
        $views = [];
        foreach ($ids as $id) {
            if (is_string($id) && isset($byId[$id])) {
                $views[] = $this->publicView($byId[$id]);
            }
        }

        return $views;
    }

    private function readShared(): array
    {
        $directory = config('canal.data_path');
        $file = $directory.'/relatos.json';
        if (! is_file($file)) {
            return ['schema_version' => 1, 'relatos' => []];
        }
        $lock = @fopen($directory.'/relatos.lock', 'c');
        if ($lock === false) {
            throw new RuntimeException('Não foi possível acessar o armazenamento.');
        }
        try {
            if (! flock($lock, LOCK_SH)) {
                throw new RuntimeException('Não foi possível obter o bloqueio de leitura.');
            }

            return $this->readDocument($file);
        } finally {
            flock($lock, LOCK_UN);
            fclose($lock);
        }
    }

    private function publicView(array $report): array
    {
        $timeline = [[
            'at' => $report['received_at'],
            'title' => 'Relato recebido',
            'message' => 'O relato foi registrado nesta demonstração.',
        ]];
        // Mensagens públicas futuras (triagem). Notas internas nunca entram aqui.
        foreach (($report['public_timeline'] ?? []) as $entry) {
            if (is_array($entry) && is_string($entry['at'] ?? null) && is_string($entry['title'] ?? null)) {
                $timeline[] = [
                    'at' => $entry['at'],
                    'title' => $entry['title'],
                    'message' => is_string($entry['message'] ?? null) ? $entry['message'] : null,
                ];
            }
        }

        return [
            'id' => $report['id'],
            'protocol' => $report['protocol'],
            'status' => $report['status'],
            'category' => config('canal.categories')[$report['category'] ?? ''] ?? 'Outro problema',
            'description' => (string) ($report['description'] ?? ''),
            'address' => (string) ($report['location']['address'] ?? ''),
            'reference' => (string) ($report['location']['reference'] ?? ''),
            'photos_count' => count($report['attachments'] ?? []),
            'received_at' => $report['received_at'],
            'updated_at' => $timeline[array_key_last($timeline)]['at'],
            'deadline' => is_string($report['public_deadline'] ?? null) ? $report['public_deadline'] : null,
            'timeline' => $timeline,
        ];
    }

    private function readDocument(string $file): array
    {
        if (! file_exists($file)) {
            return ['schema_version' => 1, 'relatos' => []];
        }
        $contents = @file_get_contents($file);
        if ($contents === false) {
            throw new RuntimeException('Não foi possível ler os registros existentes.');
        }
        $document = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
        if (! is_array($document) || ($document['schema_version'] ?? null) !== 1 || ! is_array($document['relatos'] ?? null)) {
            throw new RuntimeException('O arquivo existente não possui o formato esperado.');
        }
        foreach ($document['relatos'] as $report) {
            if (! is_array($report) || ! isset($report['submission_key_hash'], $report['protocol'], $report['receipt_secret_encrypted'], $report['received_at'], $report['status'])) {
                throw new RuntimeException('Há um registro inconsistente no arquivo existente.');
            }
        }
        return $document;
    }

    private function receipt(array $report, bool $created): array
    {
        return [
            'protocol' => $report['protocol'],
            'credential' => Crypt::decryptString($report['receipt_secret_encrypted']),
            'received_at' => $report['received_at'],
            'status' => $report['status'],
            'created' => $created,
        ];
    }

    private function storePhoto(UploadedFile $photo, string $reportId): array
    {
        $mime = $photo->getMimeType();
        $dimensions = @getimagesize($photo->getRealPath());
        if (! in_array($mime, ['image/jpeg', 'image/png'], true) || $photo->getSize() > 5 * 1024 * 1024 || ! $dimensions || $dimensions[0] * $dimensions[1] > 16000000) {
            throw new RuntimeException('Fotografia inválida ou com dimensões excessivas.');
        }

        $image = @imagecreatefromstring(file_get_contents($photo->getRealPath()));
        if ($image === false) {
            throw new RuntimeException('Não foi possível processar a fotografia.');
        }
        ob_start();
        try {
            imagesavealpha($image, true);
            $encoded = $mime === 'image/png' ? imagepng($image, null, 6) : imagejpeg($image, null, 88);
            $bytes = ob_get_contents();
        } finally {
            ob_end_clean();
            imagedestroy($image);
        }
        if (! $encoded || ! is_string($bytes)) {
            throw new RuntimeException('Não foi possível preparar a fotografia.');
        }
        $extension = $mime === 'image/png' ? 'png' : 'jpg';
        $path = 'evidence/'.$reportId.'/'.Str::uuid().'.'.$extension;
        try {
            if (! Storage::disk('local')->put($path, $bytes, ['visibility' => 'private'])) {
                throw new RuntimeException('Não foi possível armazenar a fotografia.');
            }
        } catch (Throwable $exception) {
            // O driver pode ter criado um arquivo parcial antes de falhar.
            try { Storage::disk('local')->delete($path); } catch (Throwable) { /* Preservar o erro original. */ }
            throw $exception;
        }
        return ['id' => (string) Str::uuid(), 'path' => $path, 'mime' => $mime, 'bytes' => strlen($bytes), 'visibility' => 'private'];
    }
}
