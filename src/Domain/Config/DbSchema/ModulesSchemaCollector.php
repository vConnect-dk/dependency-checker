<?php
declare(strict_types=1);

namespace Vconnect\IntegrityChecker\Domain\Config\DbSchema;

use Vconnect\IntegrityChecker\Application\Filesystem\DirectoryRegistry;
use Vconnect\IntegrityChecker\Domain\Package\Config\DbSchema;
use Vconnect\IntegrityChecker\Domain\PackagesRegistry;

class ModulesSchemaCollector
{
    /** @var array<string, string> */
    private ?array $schemaModuleRelationsMap = null;

    public function __construct(
        private readonly PackagesRegistry $packagesRegistry
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
     *
     * @return array
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

        return array_map(fn(\SplPriorityQueue $candidates): string => $candidates->top(), $candidates);
    }

    /**
     * Collect DB Schema Config from root app/etc/db_schema.xml.
     *
     * @return array
     */
    private function collectRootConfig(): array
    {
        $candidates = [];
        $fileInfo = new \SplFileInfo(DirectoryRegistry::getRoot() . 'app/etc/db_schema.xml');

        $content = null;
        if ($fileInfo->isReadable()) {
            $content = new \DOMDocument();
            $content->loadXML($fileInfo->openFile()->fread($fileInfo->getSize()));
        }

        $config = new DbSchema($content);

        if (!$config->getContent()) {
            return $candidates;
        }
        foreach ($config->getContent()['table'] as $tableName => $tableDefinition) {
            $this->collectOwnerCandidates(
                $candidates,
                $tableName,
                $tableDefinition,
                'magento/framework'
            );
        }

        return $candidates;
    }

    /**
     * Collect candidates to be owner of that table.
     *
     * @param \SplPriorityQueue[] $candidates
     * @param string $tableName
     * @param array $tableDefinition
     * @param string $packageName
     *
     * @return void
     */
    private function collectOwnerCandidates(
        array &$candidates,
        string $tableName,
        array $tableDefinition,
        string $packageName
    ): void {
        $tableCandidates = $candidates[$tableName] ?? new \SplPriorityQueue();
        foreach ($this->getTableOwnerCandidatesPriorityRules() as $priority => $rule) {
            if ($rule($tableDefinition)) {
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
            5 => [$this, 'hasResourceAndPrimaryKey'],
            4 => [$this, 'hasPrimaryKey'],
            3 => [$this, 'hasResource'],
            2 => [$this, 'hasConstraint'],
            1 => [$this, 'hasIndex'],
            0 => fn(array $tableDefinition) => true
        ];
    }

    /**
     * Try to define if table definition looks like a primary table declaration.
     * Typically, you often define table resource and primary key when you create it - so they are 2 main criteria to
     * be first priority
     *
     * @param array $tableDefinition
     *
     * @return bool
     */
    private function hasResourceAndPrimaryKey(array $tableDefinition): bool
    {
        return $this->hasResource($tableDefinition) && $this->hasPrimaryKey($tableDefinition);
    }

    private function hasPrimaryKey(array $tableDefinition): bool
    {
        foreach ($tableDefinition['constraint'] ?? [] as $constraint) {
            if ($constraint['type'] == 'primary') {
                return true;
            }
        }

        return false;
    }

    private function hasResource(array $tableDefinition): bool
    {
        return !empty($tableDefinition['resource']);
    }

    private function hasConstraint(array $tableDefinition): bool
    {
        return !empty($tableDefinition['constraint']);
    }

    private function hasIndex(array $tableDefinition): bool
    {
        return !empty($tableDefinition['index']);
    }
}
