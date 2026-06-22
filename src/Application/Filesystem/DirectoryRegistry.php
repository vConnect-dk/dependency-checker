<?php
declare(strict_types=1);

namespace Vconnect\IntegrityChecker\Application\Filesystem;

class DirectoryRegistry
{
    private static ?string $rootDirectory = null;

    public static function setRoot(string $rootDirectory): void
    {
        self::$rootDirectory = $rootDirectory;
    }

    public static function getRoot(): string
    {
        if (self::$rootDirectory === null) {
            throw new \RuntimeException('Root directory is not set');
        }

        return self::$rootDirectory;
    }

    /**
     * Reset root directory. Primarily for testing purposes.
     */
    public static function reset(): void
    {
        self::$rootDirectory = null;
    }
}
