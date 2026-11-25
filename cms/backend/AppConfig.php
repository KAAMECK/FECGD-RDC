<?php
declare(strict_types=1);
namespace Fecgd\Backend;

final class AppConfig
{
    public function __construct(
        public readonly string $root,
        public readonly string $privateDirectory,
        public readonly string $baseUrl,
        public readonly string $environment
    ) {
        if (str_starts_with($baseUrl, '//') || ($baseUrl !== '' && !preg_match('~^/[A-Za-z0-9/_-]+$~D', $baseUrl))) {
            throw new \InvalidArgumentException('Chemin public invalide.');
        }
        if (!in_array($environment, ['local', 'test', 'production'], true)) {
            throw new \InvalidArgumentException('Environnement invalide.');
        }
        if (!preg_match('~^(?:[A-Za-z]:[\\\\/]|/)~', $privateDirectory)) {
            throw new \InvalidArgumentException('Le dossier privé doit être un chemin absolu.');
        }
    }

    public static function fromEnvironment(string $root): self
    {
        $private = getenv('FECGD_PRIVATE_DIR');
        $base = getenv('FECGD_BASE_URL');
        $environment = getenv('FECGD_ENV');
        return new self($root, $private === false ? $root.'/local-private' : rtrim($private, '/\\'), $base === false ? '/FECGD-DRC' : rtrim($base, '/'), $environment === false ? 'local' : $environment);
    }

    public function databasePath(): string { return $this->privateDirectory.'/fecgd.sqlite'; }
    public function backupDirectory(): string { return $this->privateDirectory.'/backups'; }
}
