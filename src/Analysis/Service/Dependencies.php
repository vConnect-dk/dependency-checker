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
        if ($package->getPackageType() !== PackagesRegistry::MAGENTO_MODULE_PACKAGE_TYPE) {
            return [];
        }

        try {
            // Convert Magento\ZZZ -> Magento_ZZZ
            $declaredModuleXml = array_map(
                fn (string $moduleName) => str_replace('_', '\\', $moduleName),
                $package->getModuleXmlDependencies()
            );
        } catch (FileNotFoundException $exception) {
            $declaredModuleXml = [];
        }

        // leave only Magento 2 modules
        $dependenciesModules = array_filter(
            $dependencies->getHardDependencies(),
            fn (string $namespace) => $this->packagesRegistry->getPackageType(
                (string)$this->packagesRegistry->getPackageNameByNamespace($namespace)
            ) === PackagesRegistry::MAGENTO_MODULE_PACKAGE_TYPE
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
        $dependenciesPackages[DependencyInterface::TYPE_SOFT] =
            $this->getPackageNameByNamespace($dependencies->getSoftDependencies());
        $dependenciesPackages[DependencyInterface::TYPE_HARD] =
            $this->getPackageNameByNamespace($dependencies->getHardDependencies());

        try {
            $composerDeps[DependencyInterface::TYPE_HARD] = $package->getComposerRequirePackages();
            $composerDeps[DependencyInterface::TYPE_SOFT] = $package->getComposerSuggestPackages();
        } catch (FileNotFoundException $exception) {
            $composerDeps = [];
        }

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
}
