<?php

declare(strict_types=1);

namespace Vconnect\IntegrityChecker\Analysis\Service\Dependencies\Scanner;

use Vconnect\IntegrityChecker\Analysis\Service\Dependencies\Scanner\ScannerResult\ScannerResultFactory;
use Vconnect\IntegrityChecker\Analysis\Service\Dependencies\Scanner\ScannerResult\ScannerResultInterface;
use Vconnect\IntegrityChecker\Analysis\Service\Dependencies\Scanner\XmlConfigFiles\XmlFileAnalysis;
use Vconnect\IntegrityChecker\Domain\Package;

class XmlConfigFiles implements DependenciesScannerInterface
{
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
        XmlFileAnalysis::TEXT_NODES => [
            '//*[@xsi:type="object"]',
            './/frontend_model',
            './/backend_model',
            './/source_model'
        ]
    ];

    public function __construct(
        private readonly XmlFileAnalysis      $xmlFileAnalysis,
        private readonly ScannerResultFactory $scannerResultFactory
    ) {
    }

    /**
     * Search for dependencies in .xml inside the module directory.
     *
     *
     * @return ScannerResultInterface - list of packages founded as dependencies inside package's files.
     */
    public function lookupDependencies(Package $package): ScannerResultInterface
    {
        $scannerResult = $this->scannerResultFactory->create();
        $scannerResult->addSoftDependencies(
            $this->xmlFileAnalysis->analyze(
                $package,
                self::NODE_MAP_SOFT_DEPENDENCY
            )
        );
        $scannerResult->addHardDependencies(
            $this->xmlFileAnalysis->analyze(
                $package,
                self::NODE_MAP_HARD_DEPENDENCY
            )
        );

        return $scannerResult;
    }
}
