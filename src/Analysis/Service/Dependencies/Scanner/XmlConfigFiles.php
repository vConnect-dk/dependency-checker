<?php
declare(strict_types=1);

namespace Vconnect\IntegrityChecker\Analysis\Service\Dependencies\Scanner;

use Vconnect\IntegrityChecker\Analysis\Service\Dependencies\Scanner\ScannerResult\ScannerResult;
use Vconnect\IntegrityChecker\Analysis\Service\Dependencies\Scanner\ScannerResult\ScannerResultInterface;
use Vconnect\IntegrityChecker\Domain\Package;

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
        $scannerResult = new ScannerResult();
        if ($package->getXmlFilesDomDocuments()) {
            $scannerResult->setSoftDependencies(
                array_unique(
                    $this->xmlFileAnalysis->analyze(
                        $package->getXmlFilesDomDocuments(),
                        $package->getPackageNamespaces(),
                        self::NODE_MAP_SOFT_DEPENDENCY
                    )
                )
            );
            $scannerResult->setHardDependencies(
                array_unique(
                    $this->xmlFileAnalysis->analyze(
                        $package->getXmlFilesDomDocuments(),
                        $package->getPackageNamespaces(),
                        self::NODE_MAP_HARD_DEPENDENCY
                    )
                )
            );
        }

        return $scannerResult;
    }
}
