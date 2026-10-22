<?php

namespace App\Services;

use DomainException;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use RuntimeException;

/** Servidores do órgão municipal. Senhas somente como hash; sem Eloquent ou banco. */
class JsonUserRepository
{
    use LocksJsonFiles;

    public function findById(string $id): ?array
    {
        return collect($this->read()['usuarios'])->firstWhere('id', $id);
    }

    public function findByEmail(string $email): ?array
    {
        $email = Str::lower(trim($email));

        return collect($this->read()['usuarios'])->firstWhere('email', $email);
    }

    /** @throws DomainException quando o e-mail já está cadastrado */
    public function create(string $name, string $email, string $password): array
    {
        $email = Str::lower(trim($email));

        return $this->withLock('usuarios.lock', function (string $directory) use ($name, $email, $password) {
            $file = $directory.'/usuarios.json';
            $document = $this->readFile($file);
            if (collect($document['usuarios'])->contains('email', $email)) {
                throw new DomainException('E-mail já cadastrado.');
            }
            $user = [
                'id' => (string) Str::uuid(),
                'name' => trim($name),
                'email' => $email,
                'password' => Hash::make($password),
                'created_at' => now()->utc()->toIso8601String(),
            ];
            $document['usuarios'][] = $user;
            $this->writeJson($file, $document);

            return $user;
        });
    }

    private function read(): array
    {
        return $this->withLock('usuarios.lock', fn (string $directory) => $this->readFile($directory.'/usuarios.json'), LOCK_SH);
    }

    private function readFile(string $file): array
    {
        if (! file_exists($file)) {
            return ['schema_version' => 1, 'usuarios' => []];
        }
        $document = json_decode((string) file_get_contents($file), true, 512, JSON_THROW_ON_ERROR);
        if (! is_array($document) || ($document['schema_version'] ?? null) !== 1 || ! is_array($document['usuarios'] ?? null)) {
            throw new RuntimeException('O arquivo de usuários não possui o formato esperado.');
        }

        return $document;
    }
}
