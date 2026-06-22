<?php

declare(strict_types=1);

namespace Vconnect\IntegrityChecker\Domain\GraphQlSchema;

use Vconnect\IntegrityChecker\Domain\PackagesRegistry;

class GraphQlReader
{
    private ?array $definitionsRuntimeCache = null;

    public function __construct(
        private readonly PackagesRegistry $packagesRegistry
    ) {
    }

    public function getAllGraphQlTypesDefinitions(): array
    {
        if ($this->definitionsRuntimeCache === null) {
            $definitions = [];
            foreach ($this->collectGraphQlSchemaFiles() as $package => $partialSchemaContent) {
                $partialSchemaTypes = $this->parseTypesWithUnionHandling($partialSchemaContent);

                foreach ($partialSchemaTypes as $type => $partialSchemaType) {
                    if ($type === 'Query') {
                        continue;
                    }
                    if ($type === 'Mutation') {
                        continue;
                    }
                    $definitions[$type][$package] = $partialSchemaType;
                }
            }
            $this->definitionsRuntimeCache = $definitions;
        }

        return $this->definitionsRuntimeCache;
    }

    /**
     * Extract types as string from a larger string that represents the graphql schema using regular expressions
     *
     * The regex in parseTypes does not have the ability to split out the union data from the type below it for example
     *
     *  > union X = Y | Z
     *  >
     *  > type foo {}
     *
     * This would produce only type key from parseTypes, X, which would contain also the type foo entry.
     *
     * This wrapper does some post processing as a workaround to split out the union data from the type data below it
     * which would give us two entries, X and foo
     *
     * @return string[] [$typeName => $typeDeclaration, ...]
     */
    private function parseTypesWithUnionHandling(string $graphQlSchemaContent): array
    {
        $graphQlSchemaContent = $this->cutOffComments($graphQlSchemaContent);
        $types = $this->parseTypes($graphQlSchemaContent);

        /*
         * A union schema contains also the data from the schema below it
         *
         * If there are two newlines in this union schema then it has data below its definition, meaning it contains
         * type information not relevant to its actual type
         */
        $unionTypes = array_filter(
            $types,
            fn (string $t): bool => (str_contains($t, 'union ')) && (str_contains($t, PHP_EOL . PHP_EOL))
        );

        foreach ($unionTypes as $type => $schema) {
            $splitSchema = explode(PHP_EOL . PHP_EOL, (string)$schema);
            // Get the type data at the bottom, this will be the additional type data not related to the union
            $additionalTypeSchema = end($splitSchema);
            // Parse the additional type from the bottom so we can have its type key => schema pair
            $additionalTypeData = $this->parseTypes($additionalTypeSchema);
            // Fix the union type schema so it does not contain the definition below it
            $types[$type] = str_replace($additionalTypeSchema, '', $schema);
            // Append the additional data to types array
            $additionalTypeKey = array_key_first($additionalTypeData);
            $types[$additionalTypeKey] = $additionalTypeData[$additionalTypeKey];
        }

        return $types;
    }

    /**
     * Extract types as string from a larger string that represents the graphql schema using regular expressions
     *
     * @return string[] [$typeName => $typeDeclaration, ...]
     */
    private function parseTypes(string $graphQlSchemaContent): array
    {
        $typeKindsPattern = '(type|interface|union|enum|input)';
        $typeNamePattern = '([_A-Za-z][_0-9A-Za-z]+)';
        $typeDefinitionPattern = '([^\{\}]*)(\{[^\}]*\})';
        $spacePattern = '[\s\t\n\r]+';

        preg_match_all(
            "/{$typeKindsPattern}{$spacePattern}{$typeNamePattern}{$spacePattern}{$typeDefinitionPattern}/i",
            $graphQlSchemaContent,
            $matches
        );
        return array_combine($matches[2], $matches[0]);
    }

    private function collectGraphQlSchemaFiles(): array
    {
        $files = [];
        foreach ($this->packagesRegistry->getAllPackages() as $package) {
            $graphQlSchema = $package->getConfig()->getGraphQlSchema();
            if ($graphQlSchema !== null) {
                $files[$package->getName()] = $graphQlSchema;
            }
        }

        return $files;
    }

    private function cutOffComments(string $graphQlSchemaContent): string
    {
        return preg_replace('/#.*\n/', '', $graphQlSchemaContent);
    }
}
