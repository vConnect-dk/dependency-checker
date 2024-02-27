<?php
declare(strict_types=1);

namespace Vconnect\IntegrityChecker\Analysis\Service\Dependencies\Scanner;

use Vconnect\IntegrityChecker\Analysis\Service\Dependencies\Scanner\Routes\RouteMapper;
use Vconnect\IntegrityChecker\Analysis\Service\Dependencies\Scanner\ScannerResult\ScannerResultFactory;
use Vconnect\IntegrityChecker\Analysis\Service\Dependencies\Scanner\ScannerResult\ScannerResultInterface;
use Vconnect\IntegrityChecker\Application\Observer\PhpFilesAnalysis\UrlRoutesCollector;
use Vconnect\IntegrityChecker\Domain\Package;

class Routes implements DependenciesScannerInterface
{
    public function __construct(
        private readonly RouteMapper          $routeMapper,
        private readonly ScannerResultFactory $scannerResultFactory,
        private readonly UrlRoutesCollector   $urlRoutesCollector
    ) {
    }

    public function lookupDependencies(Package $package): ScannerResultInterface
    {
        $scannerResult = $this->scannerResultFactory->create();
        $routes = $this->urlRoutesCollector->getCollectedRoutes($package);
        $deps = [];
        foreach ($routes as $route => $phpFilePath) {
            $deps[] = $this->routeMapper->getDependencyFromRoutePath($route, $phpFilePath);
        }

        $deps = array_filter(
            array_unique($deps),
            fn(?string $dep) => $dep && $dep !== $package->getName()
        );

        $scannerResult->addHardDependencies($deps);

        return $scannerResult;
    }
}
