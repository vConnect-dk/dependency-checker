<?php
declare(strict_types=1);

namespace Vconnect\IntegrityChecker\Analysis\Service\Dependencies\Scanner;

use Vconnect\IntegrityChecker\Analysis\Service\Dependencies\Scanner\PhpFiles\DDLUsageRegexpAnalyzer;
use Vconnect\IntegrityChecker\Analysis\Service\Dependencies\Scanner\ScannerResult\ScannerResultFactory;
use Vconnect\IntegrityChecker\Analysis\Service\Dependencies\Scanner\ScannerResult\ScannerResultInterface;
use Vconnect\IntegrityChecker\Domain\Config\DbSchema\ModulesSchemaCollector;
use Vconnect\IntegrityChecker\Domain\Package;

class DbDDL implements DependenciesScannerInterface
{
    public function __construct(
        private readonly DDLUsageRegexpAnalyzer $ddlUsageRegexpAnalyzer,
        private readonly ModulesSchemaCollector $tablesOwnershipMap,
        private readonly ScannerResultFactory   $scannerResultFactory
    ) {
    }

    public function lookupDependencies(Package $package): ScannerResultInterface
    {
        $scannerResult = $this->scannerResultFactory->create();

        $dependencies = array_filter(
            array_map(
                fn(string $table) => $this->tablesOwnershipMap->getSchemaOwnerPackageName($table),
                array_keys($this->collectTables($package))
            )
        );

        if ($dependencies) {
            $scannerResult->addHardDependencies(array_unique($dependencies));
        }

        return $scannerResult;
    }

    private function collectTables(Package $package): array
    {
        $tables = [];
        foreach ($package->getPackageFiles() as $file) {
            if ($file->getFileInfo()->getExtension() === 'php') {
                foreach ($this->ddlUsageRegexpAnalyzer->getTablesUsed($file->getFileInfo()) as $table) {
                    if (!isset($tables[$table])) {
                        $tables[$table] = true;
                    }
                }
            }
        }

        return $tables;
    }
}
