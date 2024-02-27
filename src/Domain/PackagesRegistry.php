<?php declare(strict_types=1);

namespace Vconnect\IntegrityChecker\Domain;

use Composer\Factory;
use Composer\IO\BufferIO;
use Composer\Package\BasePackage;
use Composer\Repository\LockArrayRepository;
use Vconnect\IntegrityChecker\Domain\Scanner\FileSystemPackagesProvider;

class PackagesRegistry
{
    public const MAGENTO_LOCAL = 'app/code';

    private array $packagesNamespaceMap = [];

    /**
     * @var Package[]
     */
    private array $allPackages = [];
    private LockArrayRepository $composerLockRepo;
    private array $devPackages;

    public function __construct()
    {
        $composer = Factory::create(
            new BufferIO(),
            ROOT_DIR . 'composer.json'
        );
        try {
            $this->composerLockRepo = $composer->getLocker()->getLockedRepository(true);
        } catch (\RuntimeException) {
            $this->composerLockRepo = $composer->getLocker()->getLockedRepository();
        }

        $this->devPackages = $composer->getLocker()->getDevPackageNames();
    }

    /**
     * @return BasePackage[]
     */
    private function getAllComposerLockPackages(): array
    {
        return $this->composerLockRepo->getPackages();
    }

    /**
     * Provide already preloaded packages by directories filter.
     *
     * @param string[] $directories absolute directory path.
     * @param bool $withDev
     *
     * @return \Generator
     */
    public function getPackages(array $directories = [], bool $withDev = true): \Generator
    {
        if ($withDev) {
            $packages = $this->getAllPackages();
        } else {
            $packages = $this->getAllPackagesExcludingDev();
        }

        foreach ($packages as $package) {
            foreach ($directories as $directory) {
                if (str_contains($package->getPath(), $directory)) {
                    yield $package;
                    break;
                }
            }
        }
    }

    /**
     * @return Package[]
     */
    public function getAllPackagesExcludingDev(): array
    {
        return array_diff_key($this->getAllPackages(), array_flip($this->devPackages));
    }

    /**
     * Get all packages from app/code and packages installed via composer.
     *
     * @return Package[]
     */
    public function getAllPackages(): array
    {
        if (!empty($this->allPackages)) {
            return $this->allPackages;
        }

        $vendorPaths = [];
        foreach ($this->getAllComposerLockPackages() as $composerLockPackage) {
            $vendorPaths[] = 'vendor' . DIRECTORY_SEPARATOR . $composerLockPackage->getName();
        }
        $fsScanner = new FileSystemPackagesProvider();

        foreach (
            $fsScanner->getPackagesRecursively([self::MAGENTO_LOCAL], fileMask: '/registration.php/') as $appCodePackage
        ) {
            $this->allPackages[$appCodePackage->getName()] = $appCodePackage;
            $this->packagesNamespaceMap[$appCodePackage->getPackageNamespaces()[0]] = $appCodePackage->getName();
        }

        foreach ($fsScanner->getPackagesByDirectPath($vendorPaths) as $vendorPackage) {
            $this->allPackages[$vendorPackage->getName()] = $vendorPackage;
            foreach ($vendorPackage->getPackageNamespaces() as $namespace) {
                $this->packagesNamespaceMap[$namespace] = $vendorPackage->getName();
            }
        }

        return $this->allPackages;
    }

    /**
     * Provide Package Name by Module Namespace.
     *
     * @param string $namespace
     *
     * @return string|null
     */
    public function getPackageNameByNamespace(string $namespace): ?string
    {
        if (empty($this->packagesNamespaceMap)) {
            $this->getAllPackages();
        }

        return $this->packagesNamespaceMap[$this->getRealPackageNamespace($namespace)] ?? null;
    }

    public function getRealPackageNamespace(string $namespace): ?string
    {
        if (empty($this->packagesNamespaceMap)) {
            $this->getAllPackages();
        }

        $parts = explode('\\', $namespace);

        for ($i = count($parts); $i >= 1; $i--) {
            $namespace = implode('\\', array_slice($parts, 0, $i));
            if (isset($this->packagesNamespaceMap[$namespace])) {
                return $namespace;
            }
        }

        return null;
    }

    public function getPackage(string $packageName): ?Package
    {
        return $this->getAllPackages()[$packageName] ?? null;
    }

    public function getPackageType(string $packageName): string
    {
        $package = $this->getPackage($packageName);
        return $package ? $package->getPackageType() : Package::UNKNOWN_PACKAGE_TYPE;
    }

    public function getAllProjectNamespaces(): array
    {
        if (empty($this->packagesNamespaceMap)) {
            $this->getAllPackages();
        }

        return array_keys($this->packagesNamespaceMap);
    }
}
