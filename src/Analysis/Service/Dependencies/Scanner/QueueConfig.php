<?php declare(strict_types=1);

namespace Vconnect\IntegrityChecker\Analysis\Service\Dependencies\Scanner;

use Vconnect\IntegrityChecker\Analysis\Service\Dependencies\Scanner\QueueConfig\ConfigAnalysis;
use Vconnect\IntegrityChecker\Analysis\Service\Dependencies\Scanner\ScannerResult\ScannerResult;
use Vconnect\IntegrityChecker\Analysis\Service\Dependencies\Scanner\ScannerResult\ScannerResultInterface;
use Vconnect\IntegrityChecker\Domain\Package;

class QueueConfig implements DependenciesScannerInterface
{
    private ConfigAnalysis $configAnalysis;

    public function __construct()
    {
        $this->configAnalysis = new ConfigAnalysis();
    }


    public function lookupDependencies(Package $package): ScannerResultInterface
    {
        $scannerResult = new ScannerResult();

        $scannerResult->addHardDependencies($this->configAnalysis->analyzeConfigFiles(
            $package->getConfig()->getQueueConfig(),
            $package->getPackageName()
        ));

        return $scannerResult;
    }

}
