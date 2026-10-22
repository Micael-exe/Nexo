<?php

namespace App\Services;

use RuntimeException;

/** Lock exclusivo/compartilhado e gravação atômica (temporário + fsync + rename) para os arquivos JSON locais. */
trait LocksJsonFiles
{
    protected function withLock(string $lockName, callable $callback, int $mode = LOCK_EX): mixed
    {
        $directory = config('canal.data_path');
        if (! is_dir($directory) && ! @mkdir($directory, 0700, true) && ! is_dir($directory)) {
            throw new RuntimeException('Não foi possível preparar o armazenamento.');
        }
        $lock = @fopen($directory.'/'.$lockName, 'c');
        if ($lock === false || ! flock($lock, $mode)) {
            throw new RuntimeException('Não foi possível acessar o armazenamento.');
        }
        try {
            return $callback($directory);
        } finally {
            flock($lock, LOCK_UN);
            fclose($lock);
        }
    }

    protected function writeJson(string $file, array $document): void
    {
        $json = json_encode($document, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        $temporary = dirname($file).'/.tmp-'.bin2hex(random_bytes(12));
        $stream = @fopen($temporary, 'x+b');
        if ($stream === false) {
            throw new RuntimeException('Não foi possível preparar o arquivo.');
        }
        try {
            if (fwrite($stream, $json) !== strlen($json) || ! fflush($stream) || (function_exists('fsync') && ! fsync($stream))) {
                throw new RuntimeException('Falha na escrita do arquivo.');
            }
            fclose($stream);
            $stream = null;
            @chmod($temporary, 0600);
            if (! @rename($temporary, $file)) {
                throw new RuntimeException('Falha ao concluir a gravação.');
            }
        } finally {
            if (is_resource($stream)) {
                fclose($stream);
            }
            if (is_file($temporary)) {
                @unlink($temporary);
            }
        }
    }
}
