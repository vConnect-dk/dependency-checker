<?php
declare(strict_types=1);

namespace Vconnect\IntegrityChecker\Analysis\Service\Dependencies\Scanner;

use Vconnect\IntegrityChecker\Analysis\Service\Dependencies\Scanner\Layout\LayoutFileScanner;
use Vconnect\IntegrityChecker\Analysis\Service\Dependencies\Scanner\ScannerResult\ScannerResultFactory;
use Vconnect\IntegrityChecker\Analysis\Service\Dependencies\Scanner\ScannerResult\ScannerResultInterface;
use Vconnect\IntegrityChecker\Domain\MagentoArea;
use Vconnect\IntegrityChecker\Domain\Package;

class Layouts implements DependenciesScannerInterface
{
    private const LAYOUT_AREA_PATHS = [
        MagentoArea::AREA_ADMINHTML => 'view/adminhtml/layout',
        MagentoArea::AREA_FRONTEND => 'view/frontend/layout',
    ];

    public function __construct(
        private readonly ScannerResultFactory $scannerResultFactory,
        private readonly LayoutFileScanner    $layoutFileScanner
    ) {
    }

    public function lookupDependencies(Package $package): ScannerResultInterface
    {
        $softDeps = [];
        $hardDeps = [];
        foreach (self::LAYOUT_AREA_PATHS as $area => $path) {
            foreach ($package->getFiles($path) as $layoutFile) {
                list ($soft, $hard) = $this->layoutFileScanner->getDependenciesFromLayoutFile($layoutFile, $area);
                $softDeps = array_merge($softDeps, $soft);
                $hardDeps = array_merge($hardDeps, $hard);
            }
        }

        $postProcess = fn($deps) => array_filter(
            array_unique($deps),
            fn(?string $dep) => $dep && $dep !== $package->getName()
        );

        $scannerResult = $this->scannerResultFactory->create();
        $scannerResult->addSoftDependencies($postProcess($softDeps));
        $scannerResult->addHardDependencies($postProcess($hardDeps));

        return $scannerResult;
    }
}
