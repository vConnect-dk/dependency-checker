<?php

declare(strict_types=1);

namespace Vconnect\IntegrityChecker\Analysis\Service;

use Vconnect\IntegrityChecker\Analysis\Data\Dependencies\Result;
use Vconnect\IntegrityChecker\Analysis\Service\Dependencies\Dependency;
use Vconnect\IntegrityChecker\Analysis\Service\Dependencies\DependencyInterface;
use Vconnect\IntegrityChecker\Analysis\Service\Dependencies\ScannerPool;
use Vconnect\IntegrityChecker\Domain\Package;
use Vconnect\IntegrityChecker\Domain\PackagesRegistry;
use Vconnect\IntegrityChecker\Exception\FileNotFoundException;

class Dependencies implements AnalyzerInterface
{
    private const ANALYSIS_SCOPE = [
        Package::MAGENTO_PACKAGE_TYPE,
        Package::MAGENTO_LIBRARY_TYPE,
        Package::MAGENTO_COMPONENT_TYPE
    ];

    public function __construct(
        private readonly ScannerPool      $scanners,
        private readonly PackagesRegistry $packagesRegistry
    ) {
    }

    /**
     * Analyze PackagesProvider dependencies and compare between declared dependencies and actually used.
     *
     *
     * @throws FileNotFoundException
     */
    public function analyse(iterable $packages): \Generator
    {
        /** @var Package $package */
        foreach ($packages as $package) {
            if (!in_array($package->getPackageType(), self::ANALYSIS_SCOPE)) {
                continue;
            }

            $dependencyModel = new Dependency();
            foreach ($this->scanners as $scanner) {
                $dependencyModel->mergeDependencies($scanner->lookupDependencies($package));
            }
            yield $this->compareDependencies($package, $dependencyModel);
        }
    }

    /**
     * Compare package dependencies with discovered dependencies.
     *
     *
     * @throws FileNotFoundException
     */
    private function compareDependencies(Package $package, Dependency $dependencies): Result
    {
        return new Result(
            $package->getName(),
            $this->compareComposerDependencies($package, $dependencies),
            $this->compareModuleXmlDependencies($package, $dependencies)
        );
    }

    /**
     * Compare found dependencies with dependencies in module.xml.
     *
     *
     * @throws FileNotFoundException
     */
    private function compareModuleXmlDependencies(Package $package, Dependency $dependencies): array
    {
        try {
            $declaredDeps = $package->getModuleXmlDependencies();
        } catch (FileNotFoundException) {
            return [];
        }

        $requiredDeps = $this->extractModuleXmlDependencies($dependencies->getHardDependencies());
        $optionalDeps = $this->extractModuleXmlDependencies($dependencies->getSoftDependencies());
        $possibleDeps = array_merge($requiredDeps, $optionalDeps);

        return [
            DependencyInterface::TYPE_EXCESSIVE => (array_diff($declaredDeps, $possibleDeps)),
            DependencyInterface::TYPE_EXPECTED => array_diff($requiredDeps, $declaredDeps)
        ];
    }

    /**
     * Compare found dependencies with dependencies in composer.json.
     *
     *
     */
    private function compareComposerDependencies(Package $package, Dependency $dependencies): array
    {
        $composerDeps[DependencyInterface::TYPE_HARD] = $package->getComposerRequirePackages();
        $composerDeps[DependencyInterface::TYPE_SOFT] = $package->getComposerSuggestPackages();
        $dependenciesPackages[DependencyInterface::TYPE_SOFT] = $this->deleteRedundantSoftDeps(
            $dependencies->getSoftDependencies(),
            $composerDeps[DependencyInterface::TYPE_HARD]
        );
        $dependenciesPackages[DependencyInterface::TYPE_HARD] =
            $dependencies->getHardDependencies();

        $result[DependencyInterface::TYPE_SOFT] = array_diff(
            $dependenciesPackages[DependencyInterface::TYPE_SOFT],
            $composerDeps[DependencyInterface::TYPE_SOFT]
        );
        $result[DependencyInterface::TYPE_HARD] = array_diff(
            $dependenciesPackages[DependencyInterface::TYPE_HARD],
            $composerDeps[DependencyInterface::TYPE_HARD]
        );

        $allDependencies = array_merge(
            $dependencies->getSoftDependencies(),
            $dependencies->getHardDependencies()
        );

        /* Covering case if soft dependency is declared in a require section - that's OK */
        $excessiveHardDeps = array_diff(
            $composerDeps[DependencyInterface::TYPE_HARD],
            $allDependencies
        );
        $excessiveSoftDeps = array_diff(
            $composerDeps[DependencyInterface::TYPE_SOFT],
            $dependencies->getSoftDependencies(),
        );
        $result[DependencyInterface::TYPE_EXCESSIVE] = array_unique(array_merge($excessiveHardDeps, $excessiveSoftDeps));

        return $result;
    }

    /**
     * Check if found dependency is already defined in require section in composer.json
     * and delete it from soft dependencies array if so
     *
     *
     */
    private function deleteRedundantSoftDeps(array $collectedSoftDeps, array $composerHardDeps): array
    {
        return array_filter(
            $collectedSoftDeps,
            fn (string $softDependency): bool => !in_array($softDependency, $composerHardDeps)
        );
    }

    private function extractModuleXmlDependencies(array $dependencies): array
    {
        return array_map(
            fn (string $packageName): ?string => $this->packagesRegistry->getPackage($packageName)
                                                              ->getConfig()
                                                              ->getModuleXml()
                                                              ->getModuleName(),
            array_filter(
                $dependencies,
                fn (string $packageName): bool => $this->packagesRegistry->getPackageType($packageName)
                    === Package::MAGENTO_PACKAGE_TYPE
            )
        );
    }
}
