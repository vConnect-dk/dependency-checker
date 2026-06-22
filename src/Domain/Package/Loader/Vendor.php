<?php

declare(strict_types=1);

namespace Vconnect\IntegrityChecker\Domain\Package\Loader;

use Vconnect\IntegrityChecker\Domain\Package;
use Vconnect\IntegrityChecker\Domain\Package\Cache;
use Vconnect\IntegrityChecker\Domain\Package\LoaderInterface;
use Vconnect\IntegrityChecker\Domain\Project\ComposerProvider;
use Vconnect\IntegrityChecker\Domain\Scanner\FileSystemPackagesProvider;

class Vendor implements LoaderInterface
{
    private array $localPackagePaths;
    private array $vendorPaths;

    public function __construct(
        private readonly FileSystemPackagesProvider $fsScanner,
        private readonly ComposerProvider $composer,
        private readonly Cache $cache
    ) {
        $this->collectPaths();
    }

    private function collectPaths(): void
    {
        $this->localPackagePaths = [];
        $this->vendorPaths = [];
        foreach ($this->composer->getComposerLockRepo()->getPackages() as $composerLockPackage) {
            match ($composerLockPackage->getDistType()) {
                'path'  => $this->localPackagePaths[] = $composerLockPackage->getDistUrl(),
                default => $this->vendorPaths[] = 'vendor' . DIRECTORY_SEPARATOR . $composerLockPackage->getName()
            };
        }
    }

    public function loadPackages(): array
    {
        $packages = [];
        foreach ($this->extractPackages() as $vendorPackage) {
            $packages[$vendorPackage->getName()] = $vendorPackage;
        }

        return $packages;
    }

    /**
     * @return iterable<Package>
     */
    private function extractPackages(): iterable
    {
        yield from $this->cache->hasCache() ? $this->cache->loadCache() : $this->loadVendorOnlyPackages();
        yield from $this->loadLocalPackages();
    }

    public function loadVendorOnlyPackages(): iterable
    {
        return $this->fsScanner->getPackagesByDirectPath($this->vendorPaths);
    }

    private function loadLocalPackages(): iterable
    {
        return $this->fsScanner->getPackagesByDirectPath($this->localPackagePaths);
    }
}
