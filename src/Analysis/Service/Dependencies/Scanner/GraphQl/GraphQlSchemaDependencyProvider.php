<?php

declare(strict_types=1);

namespace Vconnect\IntegrityChecker\Analysis\Service\Dependencies\Scanner\GraphQl;

use Vconnect\IntegrityChecker\Domain\GraphQlSchema\GraphQlReader;
use Vconnect\IntegrityChecker\Domain\Package;

/**
 * Provide information on the dependency between the modules according to the GraphQL schema.
 *
 * @SuppressWarnings("PHPMD.ExcessiveClassComplexity")
 */
class GraphQlSchemaDependencyProvider
{
    public function __construct(
        private readonly GraphQlReader               $schemaReader,
        private readonly SchemaDefinitionOwnerMapper $schemaDefinitionOwnerMapper
    ) {
    }

    public function getPackageDependencies(Package $package): array
    {
        $hardDependencies = [];
        $softDependencies = [];
        $packageName = $package->getName();
        $packageSchemaDefinitionTypes = $this->extractSchemaDefinitionTypesForPackage($packageName);
        foreach ($packageSchemaDefinitionTypes as $definitionType => $definition) {
            $softDependencies[] = $this->schemaDefinitionOwnerMapper->getSoftDependency($definitionType);
            $hardDependencies = array_merge(
                $hardDependencies,
                $this->schemaDefinitionOwnerMapper->getHardDependencies($definition)
            );
        }

        $excludeSelf = fn (?string $dependencyName): bool => $dependencyName !== null && $dependencyName !== $packageName;
        $hardDependencies = array_filter(array_unique($hardDependencies), $excludeSelf);
        $softDependencies = array_filter(array_unique($softDependencies), $excludeSelf);

        return [$hardDependencies, $softDependencies];
    }

    private function extractSchemaDefinitionTypesForPackage(string $packageName): array
    {
        $packageDefinitions = [];
        $allDefinitions = $this->schemaReader->getAllGraphQlTypesDefinitions();
        foreach ($allDefinitions as $typeName => $definitions) {
            if (isset($definitions[$packageName])) {
                $packageDefinitions[$typeName] = $definitions[$packageName];
            }
        }

        return $packageDefinitions;
    }
}
