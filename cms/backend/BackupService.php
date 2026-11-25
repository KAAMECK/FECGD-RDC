<?php
declare(strict_types=1);
namespace Fecgd\Backend;

final class BackupService
{
    /** @param list<string> $mediaDirectories */
    public function __construct(private readonly \PDO $database, private readonly string $root, private readonly string $destination, private readonly array $mediaDirectories = ['site/media', 'site/dist/assets']) {}

    public function create(): string
    {
        if ($this->database->inTransaction()) throw new \RuntimeException('Terminez l’opération en cours avant la sauvegarde.');
        if (!is_dir($this->destination) && !mkdir($this->destination, 0700, true)) throw new \RuntimeException('Dossier de sauvegarde indisponible.');
        if (file_put_contents($this->destination.'/.htaccess', "Require all denied\n") === false) throw new \RuntimeException('Protection de sauvegarde indisponible.');
        $name = 'fecgd-complet-'.date('Ymd-His').'-'.bin2hex(random_bytes(6));
        $snapshot = $this->destination.'/'.$name.'.sqlite';
        $partial = $this->destination.'/'.$name.'.partial.zip';
        $target = $this->destination.'/'.$name.'.zip';
        $zip = new \ZipArchive();
        try {
            $statement = $this->database->prepare('VACUUM INTO ?');
            $statement->execute([$snapshot]);
            $statement->closeCursor();
            $check = new \PDO('sqlite:'.$snapshot);
            $result = $check->query('PRAGMA quick_check');
            if ($result === false || $result->fetchColumn() !== 'ok') throw new \RuntimeException('La copie de la base n’est pas valide.');
            $result->closeCursor();
            $result = null;
            $check = null;
            if ($zip->open($partial, \ZipArchive::CREATE | \ZipArchive::EXCL) !== true) throw new \RuntimeException('Archive indisponible.');
            if (!$zip->addFile($snapshot, 'database/fecgd.sqlite')) throw new \RuntimeException('Copie de la base impossible.');
            $paths = ['database/fecgd.sqlite'];
            foreach ($this->mediaDirectories as $directory) {
                $folder = realpath($this->root.'/'.$directory);
                $root = realpath($this->root);
                if ($folder === false) continue;
                if ($root === false || !str_starts_with(str_replace('\\', '/', $folder).'/', str_replace('\\', '/', $root).'/')) throw new \RuntimeException('Dossier média hors du site.');
                $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($folder, \FilesystemIterator::SKIP_DOTS));
                foreach ($iterator as $file) {
                    if (!$file instanceof \SplFileInfo || !$file->isFile() || $file->isLink()) continue;
                    if (!in_array(strtolower($file->getExtension()), ['jpg','jpeg','png','webp','gif','svg','mp4','woff','woff2'], true)) continue;
                    $relative = str_replace('\\', '/', substr($file->getPathname(), strlen($root) + 1));
                    if (!$zip->addFile($file->getPathname(), $relative)) throw new \RuntimeException('Copie d’un média impossible.');
                    $paths[] = $relative;
                }
            }
            if (!$zip->close()) throw new \RuntimeException('Écriture de l’archive impossible.');
            if ($zip->open($partial) !== true) throw new \RuntimeException('Relecture de l’archive impossible.');
            $hashes = [];
            foreach ($paths as $path) {
                $stream = $zip->getStream($path);
                if ($stream === false) throw new \RuntimeException('Média absent de l’archive.');
                $hash = hash_init('sha256');hash_update_stream($hash, $stream);fclose($stream);
                $hashes[$path] = hash_final($hash);
            }
            $manifest = json_encode(['format'=>1,'created_at'=>date(DATE_ATOM),'files'=>$hashes], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
            $zip->addFromString('manifest.json', $manifest);
            $zip->close();
            $this->verify($partial);
            if (!rename($partial, $target)) throw new \RuntimeException('Finalisation impossible.');
            @chmod($target, 0600);
            return $target;
        } finally {
            if ($zip->filename !== '') $zip->close();
            // Only the two uniquely named temporary files owned by this operation.
            if (is_file($snapshot)) unlink($snapshot);
            if (is_file($partial)) unlink($partial);
        }
    }

    public function verify(string $path): int
    {
        $zip = new \ZipArchive();
        if ($zip->open($path, \ZipArchive::CHECKCONS) !== true) throw new \RuntimeException('Archive illisible.');
        try {
            $raw = $zip->getFromName('manifest.json');
            if ($raw === false) throw new \RuntimeException('Manifeste absent.');
            $manifest = json_decode($raw, true, 64, JSON_THROW_ON_ERROR);
            if (!is_array($manifest) || ($manifest['format'] ?? null) !== 1 || !is_array($manifest['files'] ?? null) || !isset($manifest['files']['database/fecgd.sqlite'])) throw new \RuntimeException('Manifeste invalide.');
            if ($zip->numFiles !== count($manifest['files']) + 1) throw new \RuntimeException('Archive non conforme au manifeste.');
            foreach ($manifest['files'] as $name => $expected) {
                if (!is_string($name) || !is_string($expected) || str_contains($name, '..') || str_starts_with($name, '/') || str_contains($name, '\\')) throw new \RuntimeException('Chemin de sauvegarde invalide.');
                $stream = $zip->getStream($name);
                if ($stream === false) throw new \RuntimeException('Fichier absent : sauvegarde incomplète.');
                $hash = hash_init('sha256');hash_update_stream($hash, $stream);fclose($stream);
                if (!hash_equals($expected, hash_final($hash))) throw new \RuntimeException('Empreinte de sauvegarde incorrecte.');
            }
            return count($manifest['files']);
        } finally { $zip->close(); }
    }
}
