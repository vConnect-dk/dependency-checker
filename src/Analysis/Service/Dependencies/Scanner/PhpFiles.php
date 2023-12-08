<?php declare(strict_types=1);

namespace Vconnect\IntegrityChecker\Analysis\Service\Dependencies\Scanner;

use Vconnect\IntegrityChecker\Analysis\Service\Dependencies\Scanner\PhpFiles\RegExpFileAnalysis;
use Vconnect\IntegrityChecker\Domain\Config\Di\PluginMap;
use Vconnect\IntegrityChecker\Domain\Package;
use Vconnect\IntegrityChecker\Analysis\Service\Dependencies\Scanner\ScannerResult\ScannerResult;
use Vconnect\IntegrityChecker\Analysis\Service\Dependencies\Scanner\ScannerResult\ScannerResultInterface;
use Vconnect\IntegrityChecker\Domain\PackagesRegistry;

class PhpFiles implements DependenciesScannerInterface
{
    private const FILE_MASKS = ['php', 'phtml'];
    private RegExpFileAnalysis $regExpFileAnalysis;
    private PluginMap $pluginMap;

    public function __construct()
    {
        $this->regExpFileAnalysis = new RegExpFileAnalysis();
        $this->pluginMap = new PluginMap();
    }

    /**
     * Search for dependencies inside the module directory.
     * Scan *.php and *.phtml files for PHP classes with regexp and collect corresponding modules which are required
     * by the package to work properly.
     *
     * @param Package $package
     *
     * @return ScannerResult - interface of packages founded as dependencies inside package's files.
     */
    public function lookupDependencies(Package $package): ScannerResultInterface
    {
        $scannerResult = new ScannerResult();

        foreach ($package->getPackageFiles() as $file) {
            if (\in_array($file->getFileInfo()->getExtension(), self::FILE_MASKS)) {
                $collectedDependencies = array_unique(
                    $this->regExpFileAnalysis->analyzeFile(
                        $file,
                        $package->getPackageNamespaces()
                    )
                );
                $scannerResult = $this->determineDependencies(
                    $package,
                    $file,
                    array_unique($collectedDependencies),
                    $scannerResult
                );
            }
        }

        return $scannerResult;
    }

    /**
     * Determine dependencies from plugin class
     *
     * @param Package $package
     * @param \SplFileInfo $file
     * @param array $collectedDependencies
     * @param ScannerResult $scannerResult
     *
     * @return ScannerResult
     */
    private function determineDependencies(
        Package $package,
        \SplFileInfo $file,
        array $collectedDependencies,
        ScannerResult $scannerResult
    ): ScannerResult {
        $classReference = $package->getClassReferenceByPath($file->getPathname());
        $pluginMap = $this->pluginMap->getPluginMap();
        $softDependencies = [];

        foreach ($collectedDependencies as $i => $dependency) {
            $packageName = PackagesRegistry::getInstance()->getPackageNameByNamespace($dependency);
            if (!$packageName) {
                unset($collectedDependencies[$i]);
                continue;
            }

            if (array_key_exists($classReference, $pluginMap) && str_starts_with($pluginMap[$classReference], $dependency)) {
                $softDependencies[] = $packageName;
                unset($collectedDependencies[$i]);
            } else {
                $collectedDependencies[$i] = $packageName;
            }
        }

        $scannerResult->addSoftDependencies($softDependencies);
        $scannerResult->addHardDependencies($collectedDependencies);

        return $scannerResult;
    }
}
