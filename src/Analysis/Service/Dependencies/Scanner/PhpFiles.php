<?php declare(strict_types=1);

namespace Vconnect\IntegrityChecker\Analysis\Service\Dependencies\Scanner;

use Vconnect\IntegrityChecker\Domain\Package;
use Vconnect\IntegrityChecker\Analysis\Service\Dependencies\Scanner\ScannerResult\ScannerResult;
use Vconnect\IntegrityChecker\Analysis\Service\Dependencies\Scanner\ScannerResult\ScannerResultInterface;

class PhpFiles implements DependenciesScannerInterface
{
    private const FILE_MASKS = ['php', 'phtml'];
    private RegExpFileAnalysis $regExpFileAnalysis;

    public function __construct()
    {
        $this->regExpFileAnalysis = new RegExpFileAnalysis();
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
     * @TODO avoid array merge in the loop
     */
    private function determineDependencies(
        Package $package,
        \SplFileInfo $file,
        array $collectedDependencies,
        ScannerResult $scannerResult
    ): ScannerResult {
        $classReference = $package->getClassReferenceByPath($file->getPathname());
        $pluginMap = $package->getPluginMap();

        if (array_key_exists($classReference, $pluginMap)) {
            foreach ($collectedDependencies as $i => $dependency) {
                if (strpos($pluginMap[$classReference], $dependency) === 0) {
                    $scannerResult->setSoftDependencies([$dependency]);
                    unset($collectedDependencies[$i]);
                }
            }
        }

        $scannerResult->setHardDependencies($collectedDependencies);

        return $scannerResult;
    }
}
