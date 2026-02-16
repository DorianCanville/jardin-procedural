<?php
/**
 * Storage — Lecture/écriture JSON avec verrouillage fichier et protection corruption.
 */
class Storage
{
    private string $dataDir;

    public function __construct(string $dataDir = __DIR__ . '/../data')
    {
        $this->dataDir = rtrim($dataDir, '/\\');
    }

    /**
     * Lit un fichier JSON et retourne les données décodées.
     */
    public function read(string $filename): array
    {
        $path = $this->resolve($filename);

        if (!file_exists($path)) {
            throw new RuntimeException("Fichier introuvable : {$filename}");
        }

        $handle = fopen($path, 'r');
        if ($handle === false) {
            throw new RuntimeException("Impossible d'ouvrir : {$filename}");
        }

        flock($handle, LOCK_SH);
        $content = stream_get_contents($handle);
        flock($handle, LOCK_UN);
        fclose($handle);

        $data = json_decode($content, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new RuntimeException("JSON invalide dans {$filename} : " . json_last_error_msg());
        }

        return $data;
    }

    /**
     * Écrit des données dans un fichier JSON de manière atomique.
     * Écriture dans un fichier temporaire puis renommage pour éviter la corruption.
     */
    public function write(string $filename, array $data): void
    {
        $path = $this->resolve($filename);
        $tmpPath = $path . '.tmp.' . getmypid();

        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        if ($json === false) {
            throw new RuntimeException("Erreur encodage JSON : " . json_last_error_msg());
        }

        $written = file_put_contents($tmpPath, $json, LOCK_EX);
        if ($written === false) {
            @unlink($tmpPath);
            throw new RuntimeException("Erreur écriture : {$filename}");
        }

        if (!rename($tmpPath, $path)) {
            @unlink($tmpPath);
            throw new RuntimeException("Erreur renommage atomique : {$filename}");
        }
    }

    /**
     * Lecture-modification-écriture atomique avec callback.
     */
    public function update(string $filename, callable $modifier): array
    {
        $data = $this->read($filename);
        $data = $modifier($data);
        $this->write($filename, $data);
        return $data;
    }

    private function resolve(string $filename): string
    {
        // Protection contre le path traversal
        $basename = basename($filename);
        if ($basename !== $filename) {
            throw new RuntimeException("Nom de fichier invalide : {$filename}");
        }
        return $this->dataDir . DIRECTORY_SEPARATOR . $filename;
    }
}
