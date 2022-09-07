<?php
declare(strict_types=1);

namespace Vconnect\IntegrityChecker\Analysis\Service\Dependencies\Scanner\DbSchema;

use Vconnect\IntegrityChecker\Domain\Package;
use Vconnect\IntegrityChecker\Domain\PackagesRegistry;
use Vconnect\IntegrityChecker\Domain\Scanner\FileSystemPackagesProvider;

class ModulesSchemaCollector
{
    private const FILE_PATH = 'etc/db_schema.xml';

    /** @var array<string, string> */
    private ?array $schemaModuleRelationsMap = null;
    private Converter $converter;
    private array $schemaCache = [];

    public function __construct()
    {
        $this->converter = new Converter();
    }

    public function getSchemaOwnerPackageName(string $schema): ?string
    {
        return $this->getMap()[$schema] ?? null;
    }

    public function getPackageSchema(Package $package): ?array
    {
        $cacheKey = $package->getPackageName();
        if (!isset($this->schemaCache[$cacheKey])) {
            $this->schemaCache[$cacheKey] = $this->loadPackageSchema($package) ?: false;
        }

        return $this->schemaCache[$cacheKey] ?: null;
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

    private function collectRelations(): array
    {
        $candidates = [];
        foreach ($this->getAllPackages() as $package) {
            if ($schema = $this->getPackageSchema($package)) {
                foreach ($schema['table'] as $tableName => $tableDefinition) {
                    $allTables[$tableName] = $tableName;
                    $this->collectOwnerCandidates(
                        $candidates,
                        $tableName,
                        $tableDefinition,
                        $package->getPackageName()
                    );
                }
            }
        }
        array_walk($candidates, fn(array &$candidate) => ksort($candidate));

        return array_map(fn(array $candidates): string => current($candidates), $candidates);
    }

    /**
     * @return \Generator|Package[]
     */
    private function getAllPackages(): \Generator
    {
        $registry = PackagesRegistry::getInstance();
        $vendorPaths = [];
        foreach ($registry->getAllComposerLockPackages() as $composerLockPackage) {
            if ($composerLockPackage->getType() == PackagesRegistry::MAGENTO_MODULE_PACKAGE_TYPE) {
                $vendorPaths[] = 'vendor' . DIRECTORY_SEPARATOR . $composerLockPackage->getName();
            }
        }
        $fsScanner = new FileSystemPackagesProvider();

        foreach ($fsScanner->getPackagesRecursively(['app/code']) as $appCodePackage) {
            yield $appCodePackage;
        }

        foreach ($fsScanner->getPackagesByDirectPath($vendorPaths) as $vendorPackage) {
            yield $vendorPackage;
        }
    }

    /**
     * Collect candidates to be owner of that table.
     *
     * @param array $candidates
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
        $tableCandidates = $candidates[$tableName] ?? [];
        foreach ($this->getTableOwnerCandidatesPriorityRules() as $priority => $rule) {
            if ($rule($tableDefinition)) {
                $tableCandidates[$priority] = $packageName;
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
            0 => [$this, 'hasResourceAndPrimaryKey'],
            1 => [$this, 'hasPrimaryKey'],
            2 => [$this, 'hasResource'],
            3 => [$this, 'hasConstraint'],
            4 => [$this, 'hasIndex'],
            5 => fn(array $tableDefinition) => true
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

    /**
     * @param Package $package
     *
     * @return array|null
     */
    private function loadPackageSchema(Package $package): ?array
    {
        $dbSchemaFile = $package->getFile(self::FILE_PATH);
        if (!$dbSchemaFile->isReadable()) {
            return null;
        }
        $dbSchemaXml = new \DOMDocument();
        $dbSchemaXml->loadXML($dbSchemaFile->openFile()->fread($dbSchemaFile->getSize()));

         return $this->converter->convert($dbSchemaXml);
    }
}
