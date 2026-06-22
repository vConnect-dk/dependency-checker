<?php

declare(strict_types=1);

namespace Vconnect\IntegrityChecker\Analysis\Service\Dependencies\Scanner;

use Vconnect\IntegrityChecker\Analysis\Service\Dependencies\Scanner\QueueConfig\ConfigAnalysis;
use Vconnect\IntegrityChecker\Analysis\Service\Dependencies\Scanner\ScannerResult\ScannerResultFactory;
use Vconnect\IntegrityChecker\Analysis\Service\Dependencies\Scanner\ScannerResult\ScannerResultInterface;
use Vconnect\IntegrityChecker\Domain\Package;

class QueueConfig implements DependenciesScannerInterface
{
    public function __construct(
        private readonly ConfigAnalysis       $configAnalysis,
        private readonly ScannerResultFactory $scannerResultFactory
    ) {
    }


    public function lookupDependencies(Package $package): ScannerResultInterface
    {
        $scannerResult = $this->scannerResultFactory->create();

        $scannerResult->addHardDependencies(
            $this->configAnalysis->analyzeConfigFiles(
                $package->getConfig()->getQueueConfig(),
                $package->getName()
            )
        );

        return $scannerResult;
    }

}
