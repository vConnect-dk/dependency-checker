<?php
declare(strict_types=1);

namespace Vconnect\IntegrityChecker\Analysis\Service\Dependencies\Scanner;

use Vconnect\IntegrityChecker\Domain\Package;
use Vconnect\IntegrityChecker\Analysis\Service\Dependencies\Scanner\ScannerResult\ScannerResult;
use Vconnect\IntegrityChecker\Analysis\Service\Dependencies\Scanner\ScannerResult\ScannerResultInterface;
use Vconnect\IntegrityChecker\Analysis\Service\Dependencies\DependencyInterface;

class XmlConfigFiles implements DependenciesScannerInterface
{
    public const TEXT_NODES = 'textNodes';

    /**
     * Array of nodes for DOMDocument to specify dependencies as 'soft'
     */
    private const NODE_MAP_SOFT_DEPENDENCY = [
        'type' => ['name'],
        'preference' => [
            'type',
            'for'
        ],
        'plugin' => ['type'],
        'virtualType' => ['type'],
    ];

    /**
     * Array of nodes for DOMDocument to specify dependencies as 'hard'
     */
    private const NODE_MAP_HARD_DEPENDENCY = [
        'extension_attributes' => ['for'],
        'attribute' => ['type'],
        self::TEXT_NODES => [
            '//*[@xsi:type="object"]',
            './/frontend_model',
            './/backend_model'
        ]
    ];

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
        $collectedDependencies = [];
        if ($package->getXmlFilesDomDocuments()) {
            $collectedDependencies[DependencyInterface::TYPE_SOFT] = $this->xmlFileAnalysis->analyze(
                $package->getXmlFilesDomDocuments(),
                $package->getPackageNamespaces(),
                self::NODE_MAP_SOFT_DEPENDENCY
            );
            $collectedDependencies[DependencyInterface::TYPE_HARD] = $this->xmlFileAnalysis->analyze(
                $package->getXmlFilesDomDocuments(),
                $package->getPackageNamespaces(),
                self::NODE_MAP_HARD_DEPENDENCY
            );
        }

        return $this->setUpScannerResult($collectedDependencies);
    }

    /**
     * @param array $collectedDependencies
     * @return ScannerResult
     */
    private function setUpScannerResult(array $collectedDependencies): ScannerResultInterface
    {
        $scannerResult = new ScannerResult();

        $scannerResult->setSoftDependencies(array_unique($collectedDependencies[DependencyInterface::TYPE_SOFT]));
        $scannerResult->setHardDependencies(array_unique($collectedDependencies[DependencyInterface::TYPE_HARD]));

        return $scannerResult;
    }
}
