<?php

declare(strict_types=1);

namespace Vconnect\IntegrityChecker\Analysis\Service\Dependencies\Scanner;

use Vconnect\IntegrityChecker\Analysis\Service\Dependencies\Scanner\ScannerResult\ScannerResultFactory;
use Vconnect\IntegrityChecker\Analysis\Service\Dependencies\Scanner\ScannerResult\ScannerResultInterface;
use Vconnect\IntegrityChecker\Domain\Config\DbSchema\ModulesSchemaCollector;
use Vconnect\IntegrityChecker\Domain\Package;

class DbSchema implements DependenciesScannerInterface
{
    public function __construct(
        private readonly ModulesSchemaCollector $schemaCollector,
        private readonly ScannerResultFactory   $scannerResultFactory
    ) {
    }

    /**
     * Search for dependencies in db_schema.xml
     *
     *
     * @return ScannerResultInterface - list of packages founded as dependencies inside package's files.
     */
    public function lookupDependencies(Package $package): ScannerResultInterface
    {
        $scannerResult = $this->scannerResultFactory->create();
        $schema = $package->getConfig()->getDbSchema();
        if ($schema->getContent()) {
            $soft = $hard = [];
            foreach ($schema->getContent()['table'] as $table) {
                $soft += $this->getSoftSchemaDependencies($table);
                $hard += $this->getHardSchemaDependencies($table);
            }

            $excludeItself = fn (string $packageName): bool => $packageName !== $package->getName();

            $soft = array_filter($soft, $excludeItself);
            $hard = array_filter($hard, $excludeItself);

            $scannerResult->addSoftDependencies($soft);
            $scannerResult->addHardDependencies($hard);
        }

        return $scannerResult;
    }

    /**
     * @return string[]
     */
    private function getSoftSchemaDependencies(array $table): array
    {
        /* Creating/Updating columns is soft dependency */
        /* Any table manipulations are soft dependencies until they are hard :) */
        return [$table['name'] => $this->schemaCollector->getSchemaOwnerPackageName($table['name'])];
    }

    /**
     * @return string[]
     */
    private function getHardSchemaDependencies(array $table): array
    {
        $hard = [];
        if (empty($table['constraint'])) {
            return [];
        }

        foreach ($table['constraint'] as $constraint) {
            if (
                $constraint['type'] == 'foreign' &&
                (!isset($constraint['disabled']) || $constraint['disabled'] === "false")
            ) {

                /* Foreign keys are hard dependencies. */
                $hard[$constraint['referenceTable']] = $this->schemaCollector->getSchemaOwnerPackageName(
                    $constraint['referenceTable']
                );
            }
        }

        return $hard;
    }
}
