<?php declare(strict_types=1);

namespace Vconnect\IntegrityChecker\Analysis\Service;

use Vconnect\IntegrityChecker\Analysis\Data\Dependencies\Result;
use Vconnect\IntegrityChecker\Analysis\Service\Dependencies\Model\Dependency;
use Vconnect\IntegrityChecker\Analysis\Service\Dependencies\Scanner\DependenciesScannerInterface;
use Vconnect\IntegrityChecker\Analysis\Service\Dependencies\Scanner\PhpFiles;
use Vconnect\IntegrityChecker\Analysis\Service\Dependencies\Scanner\XmlConfigFiles;
use Vconnect\IntegrityChecker\Exception\FileNotFoundException;
use Vconnect\IntegrityChecker\Domain\PackagesRegistry;
use Vconnect\IntegrityChecker\Domain\Package;
use Vconnect\IntegrityChecker\Analysis\Service\Dependencies\Scanner\RegExpFileAnalysis;
use Vconnect\IntegrityChecker\Analysis\Service\Dependencies\Scanner\ScannerResult\ScannerResultInterface;

class Dependencies implements AnalyzerInterface
{
    private const MAGENTO_MODULE_PACKAGE_TYPE = 'magento2-module';

    /**
     * @var DependenciesScannerInterface[]
     */
    private array $dependenciesScanner = [];

    private PackagesRegistry $packagesRegistry;

    public function __construct()
    {
        $this->packagesRegistry = PackagesRegistry::getInstance();
        $regExpFileAnalysis = new RegExpFileAnalysis;
        $this->dependenciesScanner = [
            new PhpFiles($regExpFileAnalysis),
//            new XmlConfigFiles($regExpFileAnalysis)
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
            foreach ($this->dependenciesScanner as $scanner) {
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
        if ($package->getPackageType() !== self::MAGENTO_MODULE_PACKAGE_TYPE) {
            return [];
        }

        try {
            // Convert Magento\ZZZ -> Magento_ZZZ
            $declaredModuleXml = array_map(
                fn(string $moduleName) => str_replace('_', '\\', $moduleName),
                $package->getModuleXmlDependencies()
            );
        } catch (FileNotFoundException $exception) {
            $declaredModuleXml = [];
        }

        // leave only Magento 2 modules
        $dependenciesModules = array_filter($dependencies->getHardDependency(),
            fn(string $namespace) => $this->packagesRegistry->getPackageType(
                    (string)$this->packagesRegistry->getPackageNameByNamespace($namespace)
                ) === self::MAGENTO_MODULE_PACKAGE_TYPE
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
        $dependenciesPackages['soft'] = array_filter(
            array_map(
                fn(string $namespace) => $this->packagesRegistry->getPackageNameByNamespace($namespace),
                $dependencies->getSoftDependency()
            )
        );
        $dependenciesPackages['hard'] = array_filter(
            array_map(
                fn(string $namespace) => $this->packagesRegistry->getPackageNameByNamespace($namespace),
                $dependencies->getHardDependency()
            )
        );

        try {
            $composerDeps = $package->getComposerDependencies();
        } catch (FileNotFoundException $exception) {
            $composerDeps = [];
        }

        $result ['soft'] = array_diff($dependenciesPackages['soft'], $composerDeps['soft']);
        $result ['hard'] = array_diff($dependenciesPackages['hard'], $composerDeps['hard']);
        return $result;
    }
}
