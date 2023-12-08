<?php declare(strict_types=1);

namespace Vconnect\IntegrityChecker\Domain;

use Composer\Factory;
use Composer\IO\BufferIO;
use Composer\Package\BasePackage;
use Composer\Repository\LockArrayRepository;
use Vconnect\IntegrityChecker\Domain\Scanner\FileSystemPackagesProvider;

class PackagesRegistry
{
    public const MAGENTO_MODULE_PACKAGE_TYPE = 'magento2-module';
    public const UNKNOWN_COMPOSER_PACKAGE_TYPE = 'unknown';

    public const MAGENTO_LOCAL = 'app/code';

    private array $packagesNamespaceMap = [];

    /**
     * @var Package[]
     */
    private array $allPackages = [];
    private ?array $packagesTypes = null;
    private static ?PackagesRegistry $instance = null;
    private LockArrayRepository $composerLockRepo;

    private function __construct()
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

    }

    private function __clone()
    {
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
     *
     * @return \Generator
     */
    public function getPackages(array $directories = []): \Generator
    {
        foreach ($this->getAllPackages() as $package) {
            foreach ($directories as $directory) {
                if (str_contains($package->getPackagePath(), $directory)) {
                    yield $package;
                    break;
                }
            }
        }
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
            $this->allPackages[$appCodePackage->getPackageName()] = $appCodePackage;
            $this->packagesNamespaceMap[$appCodePackage->getPackageNamespaces()[0]] = $appCodePackage->getPackageName();
        }

        foreach ($fsScanner->getPackagesByDirectPath($vendorPaths) as $vendorPackage) {
            $this->allPackages[$vendorPackage->getPackageName()] = $vendorPackage;
            foreach ($vendorPackage->getPackageNamespaces() as $namespace) {
                $this->packagesNamespaceMap[$namespace] = $vendorPackage->getPackageName();
            }
        }

        return $this->allPackages;
    }

    /**
     * Provide singleton instance of PackagesProvider Registry.
     *
     * @return PackagesRegistry
     */
    public static function getInstance(): PackagesRegistry
    {
        if (!self::$instance) {
            self::$instance = new PackagesRegistry();
        }

        return self::$instance;
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
        return $this->packagesNamespaceMap[$this->getRealPackageNamespace($namespace)] ?? null;
    }

    public function getRealPackageNamespace(string $namespace): ?string
    {
        $parts = explode('\\', $namespace);

        for ($i = count($parts); $i >= 1; $i--) {
            $namespace = implode('\\', array_slice($parts, 0, $i));
            if (isset($this->packagesNamespaceMap[$namespace])) {
                return $namespace;
            }
        }

        return null;
    }

    public function getPackageType(string $packageName): string
    {
        $package = $this->allPackages[$packageName] ?? null;
        return $package ? $package->getPackageType() : self::UNKNOWN_COMPOSER_PACKAGE_TYPE;
    }

    public function getAllProjectNamespaces(): array
    {
        return array_keys($this->packagesNamespaceMap);
    }
}
