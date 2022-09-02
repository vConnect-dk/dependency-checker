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
        $dbSchemaFile = $package->getFile(self::FILE_PATH);
        if (!$dbSchemaFile->isReadable()) {
            return null;
        }
        $dbSchemaXml = new \DOMDocument();
        $dbSchemaXml->loadXML($dbSchemaFile->openFile()->fread($dbSchemaFile->getSize()));

        return $this->converter->convert($dbSchemaXml);
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
        $relations = [];
        foreach ($this->getAllPackages() as $package) {
            if ($schema = $this->getPackageSchema($package)) {
                foreach ($schema['table'] as $tableName => $tableDefinition) {
                    if ($this->isPrimaryTableDefinition($tableDefinition)) {
                        $relations[$tableName] = $package->getPackageName();
                    }
                }
            }
        }

        return $relations;
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
     * Try to define if table definition looks like a primary table declaration.
     * Typically, you always define table resouce and primary key when you create it - so they are 2 main criteria.
     *
     * @param array $tableDefinition
     *
     * @return bool
     */
    private function isPrimaryTableDefinition(array $tableDefinition): bool
    {
        return !empty($tableDefinition['resource']) && $this->hasPrimaryKey($tableDefinition);
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
}
