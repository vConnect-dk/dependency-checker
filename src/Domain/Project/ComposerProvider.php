<?php
declare(strict_types=1);

namespace Vconnect\IntegrityChecker\Domain\Project;

use Composer\Factory;
use Composer\IO\BufferIO;
use Composer\Repository\LockArrayRepository;
use RuntimeException;
use Vconnect\IntegrityChecker\Application\Filesystem\DirectoryRegistry;

class ComposerProvider
{
    private LockArrayRepository $composerLockRepo;
    /** @var string[] */
    private array $devPackages;

    public function __construct()
    {
        $composer = Factory::create(
            new BufferIO(),
            DirectoryRegistry::getRoot() . 'composer.json'
        );
        try {
            $this->composerLockRepo = $composer->getLocker()->getLockedRepository(true);
        } catch (RuntimeException) {
            $this->composerLockRepo = $composer->getLocker()->getLockedRepository();
        }

        $this->devPackages = $composer->getLocker()->getDevPackageNames();
    }

    public function getDevPackages(): array
    {
        return $this->devPackages;
    }

    public function getComposerLockRepo(): LockArrayRepository
    {
        return $this->composerLockRepo;
    }
}
