<?php

declare(strict_types=1);

namespace Vconnect\IntegrityChecker\Domain\Config\DbSchema;

use Vconnect\IntegrityChecker\Domain\Package\Config\DbSchema;
use Vconnect\IntegrityChecker\Domain\PackagesRegistry;
use Vconnect\IntegrityChecker\Domain\Project\Config\Root;

class ModulesSchemaCollector
{
    /** @var array<string, string> */
    private ?array $schemaModuleRelationsMap = null;

    public function __construct(
        private readonly PackagesRegistry $packagesRegistry,
        private readonly Root $rootConfig
    ) {
    }


    public function getSchemaOwnerPackageName(string $schema): ?string
    {
        return $this->getMap()[$schema] ?? null;
    }

    /**
     * Returns an array: schema name -> module-owner of that schema
     *
     * @return array<string, string>
     */
    private function getMap(): array
    {
        if ($this->schemaModuleRelationsMap === null) {
            $this->schemaModuleRelationsMap = $this->collectRelations();
        }

        return $this->schemaModuleRelationsMap;
    }

    /**
     * Collect relations from packages.
     */
    private function collectRelations(): array
    {
        $candidates = $this->collectRootConfig();

        foreach ($this->packagesRegistry->getAllPackages() as $package) {
            $schema = $package->getConfig()->getDbSchema();
            if ($schema->getContent()) {
                foreach ($schema->getContent()['table'] as $tableName => $tableDefinition) {
                    $this->collectOwnerCandidates(
                        $candidates,
                        $tableName,
                        $tableDefinition,
                        $package->getName()
                    );
                }
            }
        }

        return array_map(fn (\SplPriorityQueue $candidates): string => $candidates->top(), $candidates);
    }

    /**
     * Collect DB Schema Config from root app/etc/db_schema.xml.
     */
    private function collectRootConfig(): array
    {
        $candidates = [];

        $content = $this->rootConfig->getRootDbSchema();
        $config = new DbSchema($content);

        if (!$config->getContent()) {
            return $candidates;
        }
        foreach ($config->getContent()['table'] as $tableName => $tableDefinition) {
            $this->collectOwnerCandidates(
                $candidates,
                $tableName,
                $tableDefinition,
                PackagesRegistry::MAGENTO_LIBRARY
            );
        }

        return $candidates;
    }

    /**
     * Collect candidates to be owner of that table.
     *
     * @param \SplPriorityQueue[] $candidates
     *
     */
    private function collectOwnerCandidates(
        array &$candidates,
        string $tableName,
        array $tableDefinition,
        string $packageName
    ): void {
        $tableCandidates = $candidates[$tableName] ?? new \SplPriorityQueue();
        foreach ($this->getTableOwnerCandidatesPriorityRules() as $rule) {
            if ($priority = $rule($packageName, $tableDefinition)) {
                $tableCandidates->insert($packageName, $priority);
                break;
            }
        }
        $candidates[$tableName] = $tableCandidates;
    }

    /**
     * @return callable[]
     */
    private function getTableOwnerCandidatesPriorityRules(): array
    {
        return [
            $this->isMagentoCorePackage(...),
            $this->hasResourceAndPrimaryKey(...),
            $this->hasPrimaryKey(...),
            $this->hasResource(...),
            $this->hasConstraint(...),
            $this->hasIndex(...),
            fn (string $packageName, array $tableDefinition): int => 1
        ];
    }

    private function isMagentoCorePackage(string $packageName): int
    {
        return $this->packagesRegistry->getTopologicallySortedCorePackages()[$packageName] ?? 0;
    }

    /**
     * Try to define if table definition looks like a primary table declaration.
     * Typically, you often define table resource and primary key when you create it - so they are 2 main criteria to
     * be first priority
     *
     *
     */
    private function hasResourceAndPrimaryKey(string $packageName, array $tableDefinition): int
    {
        return $this->hasResource($tableDefinition) &&
        $this->hasPrimaryKey($tableDefinition) ? 6 : 0;
    }

    private function hasPrimaryKey(array $tableDefinition): int
    {
        foreach ($tableDefinition['constraint'] ?? [] as $constraint) {
            if ($constraint['type'] == 'primary') {
                return 5;
            }
        }

        return 0;
    }

    private function hasResource(array $tableDefinition): int
    {
        return empty($tableDefinition['resource']) ? 0 : 4;
    }

    private function hasConstraint(array $tableDefinition): int
    {
        return empty($tableDefinition['constraint']) ? 0 : 3;
    }

    private function hasIndex(array $tableDefinition): int
    {
        return empty($tableDefinition['index']) ? 0 : 2;
    }
}
