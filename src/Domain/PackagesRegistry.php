<?php declare(strict_types=1);

namespace Vconnect\IntegrityChecker\Domain;

use Generator;
use Vconnect\IntegrityChecker\Domain\Package\LoaderInterface;
use Vconnect\IntegrityChecker\Domain\Package\SortOrder\Topological;
use Vconnect\IntegrityChecker\Domain\Project\ComposerProvider;

class PackagesRegistry
{
    public const MAGENTO_LIBRARY = 'magento/framework';

    private array $packagesNamespaceMap = [];
    private ?array $topologicalSort = null;
    private Topological $topologicalSorter;

    /**
     * @var Package[]
     */
    private array $allPackages = [];

    public function __construct(
        private readonly LoaderInterface $loader,
        private readonly ComposerProvider  $lock,
    ) {
        $this->topologicalSorter = new Topological($this);
    }

    /**
     * Provide already preloaded packages by directories filter.
     *
     * @param string[] $directories absolute directory path.
     * @param bool $withDev
     *
     * @return Generator
     */
    public function getPackages(array $directories = [], bool $withDev = true): Generator
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
        return array_diff_key($this->getAllPackages(), array_flip($this->lock->getDevPackages()));
    }

    /**
     * @return Package[]
     */
    public function getMagentoModules(): array
    {
        return array_filter(
            $this->getAllPackages(),
            fn (Package $package) => $package->getPackageType() === Package::MAGENTO_PACKAGE_TYPE
        );
    }

    public function getTopologicallySortedCorePackages(): array
    {
        if (!isset($this->topologicalSort)) {
            $this->topologicalSort = $this->topologicalSorter->getTopologicallyOrderedMagentoPackages();
        }

        return $this->topologicalSort;
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
        $packages = $this->loader->loadPackages();
        foreach ($packages as $package) {
            foreach ($package->getPackageNamespaces() as $namespace) {
                $this->packagesNamespaceMap[$namespace] = $package->getName();
            }
        }

        $this->allPackages = $packages;
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
