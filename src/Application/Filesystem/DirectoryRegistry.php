<?php
declare(strict_types=1);

namespace Vconnect\IntegrityChecker\Application\Filesystem;

use DI\DependencyException;
use DI\NotFoundException;

class DirectoryRegistry
{
    public static function setRoot(string $rootDirectory): void
    {
        App()->set('root', $rootDirectory);
    }

    public static function getRoot(): ?string
    {
        try {
            return App()->get('root');
        } catch (DependencyException|NotFoundException) {
            throw new \RuntimeException('Root directory is not set');
        }
    }
}