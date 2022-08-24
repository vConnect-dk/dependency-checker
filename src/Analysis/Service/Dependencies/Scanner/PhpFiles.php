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
        $collectedDependencies = [];
        $scannerResult = new ScannerResult();
        foreach ($package->getPackageFiles() as $file) {
            if (\in_array($file->getFileInfo()->getExtension(), self::FILE_MASKS)) {
                $collectedDependencies[] = $this->regExpFileAnalysis->analyzeFile($file, $package->getPackageNamespaces());
            }
        }
        $scannerResult->setHardDependencies(array_unique(array_merge([], ...$collectedDependencies)));

        return $scannerResult;
    }
}
