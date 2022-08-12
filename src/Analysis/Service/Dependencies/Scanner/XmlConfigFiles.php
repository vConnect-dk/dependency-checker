<?php
declare(strict_types=1);

namespace Vconnect\IntegrityChecker\Analysis\Service\Dependencies\Scanner;

use Vconnect\IntegrityChecker\Domain\Package;
use Vconnect\IntegrityChecker\Analysis\Service\Dependencies\Scanner\ScannerResult\ScannerResult;
use Vconnect\IntegrityChecker\Analysis\Service\Dependencies\Scanner\ScannerResult\ScannerResultInterface;
use Vconnect\IntegrityChecker\Analysis\Service\Dependencies\DependencyInterface;

class XmlConfigFiles implements DependenciesScannerInterface
{
    private XmlFileAnalysis $xmlFileAnalysis;

    public function __construct()
    {
        $this->xmlFileAnalysis = new XmlFileAnalysis();
    }

    /**
     * Search for dependencies in .xml inside the module directory.
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
        if ($package->getXmlFilesDomDocuments()) {
            $collectedDependencies = $this->xmlFileAnalysis->getDependencies(
                $package->getXmlFilesDomDocuments(), $package->getPackageNamespaces()
            );
            $resultDependencies[DependencyInterface::TYPE_SOFT][] = $collectedDependencies[DependencyInterface::TYPE_SOFT];
            $resultDependencies[DependencyInterface::TYPE_HARD][] = $collectedDependencies[DependencyInterface::TYPE_HARD];
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
