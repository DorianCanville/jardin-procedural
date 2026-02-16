<?php
/**
 * Storage - Gestion persistante des données JSON avec verrouillage fichier.
 */
class Storage
{
    private static string $dataDir = __DIR__ . '/../data/';

    /**
     * Lit un fichier JSON et retourne son contenu décodé.
     */
    public static function read(string $filename): array
    {
        $path = self::getPath($filename);

        if (!file_exists($path)) {
            return [];
        }

        $handle = fopen($path, 'r');
        if (!$handle) {
            throw new RuntimeException("Impossible d'ouvrir: {$filename}");
        }

        flock($handle, LOCK_SH);
        $content = stream_get_contents($handle);
        flock($handle, LOCK_UN);
        fclose($handle);

        if ($content === false || $content === '') {
            return [];
        }

        $data = json_decode($content, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new RuntimeException("JSON corrompu: {$filename} - " . json_last_error_msg());
        }

        return $data;
    }

    /**
     * Écrit des données dans un fichier JSON avec verrouillage exclusif.
     * Utilise l'écriture atomique via fichier temporaire.
     */
    public static function write(string $filename, array $data): void
    {
        $path = self::getPath($filename);
        $dir = dirname($path);

        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        if ($json === false) {
            throw new RuntimeException("Erreur encodage JSON: " . json_last_error_msg());
        }

        // Écriture atomique : écrire dans un fichier temp puis renommer
        $tmpPath = $path . '.tmp.' . getmypid();
        $handle = fopen($tmpPath, 'w');
        if (!$handle) {
            throw new RuntimeException("Impossible de créer le fichier temporaire: {$tmpPath}");
        }

        flock($handle, LOCK_EX);
        $written = fwrite($handle, $json);
        fflush($handle);
        flock($handle, LOCK_UN);
        fclose($handle);

        if ($written === false) {
            unlink($tmpPath);
            throw new RuntimeException("Erreur écriture: {$filename}");
        }

        // Renommage atomique
        if (PHP_OS_FAMILY === 'Windows') {
            // Windows ne supporte pas le rename atomique sur un fichier existant
            if (file_exists($path)) {
                unlink($path);
            }
        }
        rename($tmpPath, $path);
    }

    /**
     * Met à jour un fichier JSON avec un callback.
     * Lecture + modification + écriture en une seule opération verrouillée.
     */
    public static function update(string $filename, callable $callback): array
    {
        $data = self::read($filename);
        $data = $callback($data);
        self::write($filename, $data);
        return $data;
    }

    /**
     * Vérifie si un fichier de données existe.
     */
    public static function exists(string $filename): bool
    {
        return file_exists(self::getPath($filename));
    }

    /**
     * Réinitialise un fichier avec des données par défaut.
     */
    public static function reset(string $filename, array $default): void
    {
        self::write($filename, $default);
    }

    private static function getPath(string $filename): string
    {
        // Protection traversée de répertoire
        $filename = basename($filename);
        return self::$dataDir . $filename;
    }
}
