<?php declare(strict_types=1);

namespace Vconnect\IntegrityChecker\Analysis\Service;

use Vconnect\IntegrityChecker\Analysis\Data\Dependencies\Result;
use Vconnect\IntegrityChecker\Analysis\Service\Dependencies\Dependency;
use Vconnect\IntegrityChecker\Analysis\Service\Dependencies\DependencyInterface;
use Vconnect\IntegrityChecker\Analysis\Service\Dependencies\Scanner\DbSchema;
use Vconnect\IntegrityChecker\Analysis\Service\Dependencies\Scanner\DependenciesScannerInterface;
use Vconnect\IntegrityChecker\Analysis\Service\Dependencies\Scanner\PhpFiles;
use Vconnect\IntegrityChecker\Analysis\Service\Dependencies\Scanner\XmlConfigFiles;
use Vconnect\IntegrityChecker\Domain\Package;
use Vconnect\IntegrityChecker\Domain\PackagesRegistry;
use Vconnect\IntegrityChecker\Exception\FileNotFoundException;

class Dependencies implements AnalyzerInterface
{
    /**
     * @var DependenciesScannerInterface[]
     */
    private array $scanners = [];

    private PackagesRegistry $packagesRegistry;

    public function __construct()
    {
        $this->packagesRegistry = PackagesRegistry::getInstance();
        $this->scanners = [
            new PhpFiles(),
            new XmlConfigFiles(),
            new DbSchema()
        ];
    }

    /**
     * Analyze PackagesProvider dependencies and compare between declared dependencies and actually used.
     *
     * @param iterable $packages
     *
     * @return \Generator
     */
    public function analyse(iterable $packages): \Generator
    {
        foreach ($packages as $package) {
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
     * @param Package $package
     * @param Dependency $dependencies
     *
     * @return Result
     */
    private function compareDependencies(Package $package, Dependency $dependencies): Result
    {
        return new Result(
            $package->getPackageName(),
            $this->compareComposerDependencies($package, $dependencies),
            $this->compareModuleXmlDependencies($package, $dependencies)
        );
    }

    /**
     * Compare found dependencies with dependencies in module.xml.
     *
     * @param Package $package
     * @param Dependency $dependencies
     *
     * @return array
     */
    private function compareModuleXmlDependencies(Package $package, Dependency $dependencies): array
    {
        try {
            $declaredModuleXml = $package->getModuleXmlDependencies();
        } catch (FileNotFoundException $exception) {
            return [];
        }

        // Convert Magento\ZZZ -> Magento_ZZZ
        $declaredModuleXml = array_map(
            fn (string $moduleName) => str_replace('_', '\\', $moduleName),
            $declaredModuleXml
        );

        // leave only Magento 2 modules
        $dependenciesModules = array_filter(
            $dependencies->getHardDependencies(),
            fn (string $namespace) => in_array($this->packagesRegistry->getPackageType(
                (string)$this->packagesRegistry->getPackageNameByNamespace($namespace)
            ), [PackagesRegistry::MAGENTO_MODULE_PACKAGE_TYPE, PackagesRegistry::UNKNOWN_COMPOSER_PACKAGE_TYPE])
        );

        return array_diff($dependenciesModules, $declaredModuleXml);
    }

    /**
     * Compare found dependencies with dependencies in composer.json.
     *
     * @param Package $package
     * @param Dependency $dependencies
     *
     * @return array
     */
    private function compareComposerDependencies(Package $package, Dependency $dependencies): array
    {
        try {
            $composerDeps[DependencyInterface::TYPE_HARD] = $package->getComposerRequirePackages();
            $composerDeps[DependencyInterface::TYPE_SOFT] = $package->getComposerSuggestPackages();
        } catch (FileNotFoundException) {
            $composerDeps = [DependencyInterface::TYPE_HARD => [], DependencyInterface::TYPE_SOFT => []];
        }
        $dependenciesPackages[DependencyInterface::TYPE_SOFT] = $this->deleteRedundantSoftDeps(
            $this->getPackageNameByNamespace($dependencies->getSoftDependencies()),
            $composerDeps[DependencyInterface::TYPE_HARD]
        );
        $dependenciesPackages[DependencyInterface::TYPE_HARD] =
            $this->getPackageNameByNamespace($dependencies->getHardDependencies());

        $result[DependencyInterface::TYPE_SOFT] = array_diff(
            $dependenciesPackages[DependencyInterface::TYPE_SOFT],
            $composerDeps[DependencyInterface::TYPE_SOFT]
        );
        $result[DependencyInterface::TYPE_HARD] = array_diff(
            $dependenciesPackages[DependencyInterface::TYPE_HARD],
            $composerDeps[DependencyInterface::TYPE_HARD]
        );

        return $result;
    }

    /**
     * @param array $dependency
     *
     * @return array
     */
    private function getPackageNameByNamespace(array $dependency): array
    {
        return array_filter(
            array_map(
                fn (string $namespace) => $this->packagesRegistry->getPackageNameByNamespace($namespace),
                $dependency
            )
        );
    }

    /**
     * Check if found dependency is already defined in require section in composer.json
     * and delete it from soft dependencies array if so
     *
     * @param array $collectedSoftDeps
     * @param array $composerHardDeps
     *
     * @return array
     */
    private function deleteRedundantSoftDeps(array $collectedSoftDeps, array $composerHardDeps): array
    {
        return array_filter(
            $collectedSoftDeps,
            fn(string $softDependency) => !in_array($softDependency, $composerHardDeps)
        );
    }
}
