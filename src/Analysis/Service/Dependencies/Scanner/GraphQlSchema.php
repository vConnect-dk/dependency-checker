<?php declare(strict_types=1);

namespace Vconnect\IntegrityChecker\Analysis\Service\Dependencies\Scanner;

use Vconnect\IntegrityChecker\Analysis\Service\Dependencies\Scanner\GraphQl\GraphQlSchemaDependencyProvider;
use Vconnect\IntegrityChecker\Analysis\Service\Dependencies\Scanner\ScannerResult\ScannerResultFactory;
use Vconnect\IntegrityChecker\Analysis\Service\Dependencies\Scanner\ScannerResult\ScannerResultInterface;
use Vconnect\IntegrityChecker\Domain\Package;

class GraphQlSchema implements DependenciesScannerInterface
{
    public function __construct(
        private readonly GraphQlSchemaDependencyProvider $graphQlSchemaDependencyProvider,
        private readonly ScannerResultFactory            $scannerResultFactory
    ) {
    }

    public function lookupDependencies(Package $package): ScannerResultInterface
    {
        $scannerResult = $this->scannerResultFactory->create();

        list($hard, $soft) = $this->graphQlSchemaDependencyProvider->getPackageDependencies($package);

        $scannerResult->addSoftDependencies($soft);
        $scannerResult->addHardDependencies($hard);

        return $scannerResult;
    }
}
