<?php
declare(strict_types=1);

namespace Vconnect\IntegrityChecker\Analysis\Service\Dependencies\Scanner\GraphQl;


use Vconnect\IntegrityChecker\Domain\GraphQlSchema\GraphQlReader;
use Vconnect\IntegrityChecker\Domain\Package;

/**
 * Provide information on the dependency between the modules according to the GraphQL schema.
 *
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class GraphQlSchemaDependencyProvider
{
    private array $schemaTypeOwners = [];

    public function __construct(
        private readonly GraphQlReader               $schemaReader,
        private readonly SchemaDefinitionOwnerMapper $schemaDefinitionOwnerMapper
    ) {
    }

    /**
     * @param Package $package
     * @return array
     */
    public function getPackageDependencies(Package $package): array
    {
        $dependencies = [];
        $packageName = $package->getPackageName();
        $packageSchemaDefinitionTypes = $this->extractSchemaDefinitionTypesForPackage($packageName);
        foreach ($packageSchemaDefinitionTypes as $definitionType) {
            $dependencies[] = $this->schemaDefinitionOwnerMapper->getOwner($definitionType);
        }

        $dependencies = array_filter($dependencies, fn(string $dependencyName) => $dependencyName != $packageName);

        return array_unique($dependencies);
    }

    private function extractSchemaDefinitionTypesForPackage(string $packageName): array
    {
        $packageDefinitions = [];
        $allDefinitions = $this->schemaReader->getAllGraphQlTypesDefinitions();
        foreach ($allDefinitions as $typeName => $definitions) {
            if (isset($definitions[$packageName])) {
                $packageDefinitions[] = $typeName;
            }
        }

        return $packageDefinitions;
    }
}
