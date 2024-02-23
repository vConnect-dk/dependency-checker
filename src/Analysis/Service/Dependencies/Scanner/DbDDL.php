<?php
declare(strict_types=1);

namespace Vconnect\IntegrityChecker\Analysis\Service\Dependencies\Scanner;

use Vconnect\IntegrityChecker\Analysis\Service\Dependencies\Scanner\DbDDL\PackageTablesUsageRegistry;
use Vconnect\IntegrityChecker\Analysis\Service\Dependencies\Scanner\ScannerResult\ScannerResultFactory;
use Vconnect\IntegrityChecker\Analysis\Service\Dependencies\Scanner\ScannerResult\ScannerResultInterface;
use Vconnect\IntegrityChecker\Domain\Config\DbSchema\ModulesSchemaCollector;
use Vconnect\IntegrityChecker\Domain\Package;

class DbDDL implements DependenciesScannerInterface
{
    public function __construct(
        private readonly PackageTablesUsageRegistry $packageTablesUsageRegistry,
        private readonly ModulesSchemaCollector     $tablesOwnershipMap,
        private readonly ScannerResultFactory       $scannerResultFactory
    ) {
    }

    public function lookupDependencies(Package $package): ScannerResultInterface
    {
        $scannerResult = $this->scannerResultFactory->create();

        $packageName = $package->getName();
        $dependencies = array_filter(
            array_map(
                fn(string $table) => $this->tablesOwnershipMap->getSchemaOwnerPackageName($table),
                $this->packageTablesUsageRegistry->get($package)
            ),
            fn(?string $dependency) => $dependency && $dependency !== $packageName
        );

        if ($dependencies) {
            $scannerResult->addHardDependencies(array_unique($dependencies));
        }

        return $scannerResult;
    }
}
