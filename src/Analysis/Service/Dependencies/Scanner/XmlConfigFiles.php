<?php
declare(strict_types=1);

namespace Vconnect\IntegrityChecker\Analysis\Service\Dependencies\Scanner;

use Vconnect\IntegrityChecker\Domain\Package;
use Vconnect\IntegrityChecker\Analysis\Service\Dependencies\Scanner\ScannerResult\ScannerResult;
use Vconnect\IntegrityChecker\Analysis\Service\Dependencies\Scanner\ScannerResult\ScannerResultInterface;
use Vconnect\IntegrityChecker\Analysis\Service\Dependencies\Model\DependencyInterface;

class XmlConfigFiles implements DependenciesScannerInterface
{
    private const FILE_MASKS = ['di.xml', 'system.xml', 'extension_attributes.xml'];
    private XmlFileAnalysis $xmlFileAnalysis;

    public function __construct()
    {
        $this->xmlFileAnalysis = new XmlFileAnalysis();
    }

    /**
     * Search for dependencies inside the module directory.
     * Scan di.xml', 'system.xml', 'extension_attributes.xml' files for PHP classes with regexp
     * and collect corresponding modules which are required by the package to work properly.
     *
     * @param Package $package
     *
     * @return ScannerResultInterface - list of packages founded as dependencies inside package's files.
     */
    public function lookupDependencies(Package $package): ScannerResultInterface
    {
        $resultDependencies = [
            DependencyInterface::TYPE_SOFT => [],
            DependencyInterface::TYPE_HARD => []
        ];
        foreach ($package->getPackageFiles() as $file) {
            if (in_array($file->getFilename(), self::FILE_MASKS)) {
                $collectedDependencies = $this->xmlFileAnalysis->getDependencies($file, $package->getPackageNamespaces());
                $resultDependencies[DependencyInterface::TYPE_SOFT][] = $collectedDependencies[DependencyInterface::TYPE_SOFT];
                $resultDependencies[DependencyInterface::TYPE_HARD][] = $collectedDependencies[DependencyInterface::TYPE_HARD];
            }
        }

        return $this->setUpScannerResult($resultDependencies);
    }

    /**
     * @param array $resultDependencies
     * @return ScannerResult
     */
    private function setUpScannerResult(array $resultDependencies): ScannerResultInterface
    {
        $scannerResult = new ScannerResult();

        $scannerResult->setSoftDependencies(array_unique(
            array_merge([], ...$resultDependencies[DependencyInterface::TYPE_SOFT]))
        );
        $scannerResult->setHardDependencies(array_unique(
            array_merge([], ...$resultDependencies[DependencyInterface::TYPE_HARD]))
        );

        return $scannerResult;
    }
}
