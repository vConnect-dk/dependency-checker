<?php

declare(strict_types=1);

namespace Vconnect\IntegrityChecker\Analysis\Service\Dependencies\Scanner\GraphQl;

use Exception;
use GraphQL\Language\Parser;
use GraphQL\Type\Definition\Type;
use Vconnect\IntegrityChecker\Domain\GraphQlSchema\GraphQlReader;
use Vconnect\IntegrityChecker\Domain\PackagesRegistry;

class SchemaDefinitionOwnerMapper
{
    private ?array $ownersMap = null;

    public function __construct(
        private readonly GraphQlReader $schemaReader,
        private readonly PackagesRegistry $packagesRegistry
    ) {
    }

    /**
     *  We treat extending types as soft dependencies.
     */
    public function getSoftDependency(string $definitionType): ?string
    {
        return $this->getOwner($definitionType);
    }

    /**
     * Implementing interfaces, using types for fields we treat as hard dependencies.
     */
    public function getHardDependencies(string $definition): array
    {
        $typesUsed = $this->getTypesUsedInDefinition($definition);

        return array_map($this->getOwner(...), $typesUsed);
    }

    private function getOwner(string $definitionType): ?string
    {
        if ($this->ownersMap === null) {
            $this->ownersMap = $this->collectOwners();
        }

        return $this->ownersMap[$definitionType] ?? null;
    }

    private function collectOwners(): array
    {
        $allTypes = $this->schemaReader->getAllGraphQlTypesDefinitions();
        $ownersMapping = [];
        foreach ($allTypes as $type => $definitions) {
            $candidates = new \SplPriorityQueue();
            foreach ($definitions as $package => $definition) {
                $candidates->insert($package, $this->prioritizeCandidate($definition, $package));
            }

            $ownersMapping[$type] = $candidates->top();
        }

        return $ownersMapping;
    }

    private function prioritizeCandidate(
        string $definition,
        string $package
    ): ?int {
        $parsedAST = $this->parseDefinition($definition);
        $priority = 0;

        foreach ($this->getTypeOwnerPriorityRules() as $rule) {
            $priority = max($rule($package, $parsedAST), $priority);
        }

        return $priority;
    }

    /**
     * @return callable[]
     */
    private function getTypeOwnerPriorityRules(): array
    {
        return [
            $this->isMagentoCoreType(...),
            $this->isInterfaceWithTypeResolver(...),
            $this->hasInterfaceImplementation(...),
            $this->hasDescription(...),
            fn (string $package, array $parsedAST): int => 1
        ];
    }

    private function isMagentoCoreType(string $package, array $parsedAST): int
    {
        return $this->packagesRegistry->getTopologicallySortedCorePackages()[$package] ?? 0;
    }

    private function isInterfaceWithTypeResolver(string $package, array $parsedAST): int
    {
        $parsedAST = dot($parsedAST);
        $isInterface = $parsedAST['definitions.0.kind'] === 'InterfaceTypeDefinition';
        if ($isInterface) {
            foreach ($parsedAST['definitions.0.directives'] as $directive) {
                if (dot($directive)['name.value'] === 'typeResolver') {
                    return 3;
                }
            }
        }

        return 0;
    }

    private function hasInterfaceImplementation(string $package, array $parsedAST): int
    {
        $parsedAST = dot($parsedAST);
        if ($parsedAST['definitions.0.kind'] === 'ObjectTypeDefinition') {
            foreach ($parsedAST['definitions.0.interfaces'] as $interface) {
                if (dot($interface)['name.value']) {
                    return 2;
                }
            }
        }

        return 0;
    }

    private function hasDescription(string $package, array $parsedAST): int
    {
        $parsedAST = dot($parsedAST);
        foreach ($parsedAST->get('definitions.0.directives', []) as $directive) {
            $directive = dot($directive);
            if ($directive['arguments.0.name.value'] === 'description' && $directive['arguments.0.value.value']) {
                return 1;
            }
        }

        return 0;
    }

    private function getTypesUsedInDefinition(string $definition): array
    {
        $types = [];
        $ast = dot($this->parseDefinition($definition)['definitions'][0] ?? []);
        foreach ($ast->get('interfaces', []) as $interface) {
            $types[] = $interface['name']['value'];
        }

        foreach (dot($ast->get('fields', []))->flatten() as $flattenKey => $fieldProperty) {
            if (str_ends_with((string) $flattenKey, '.type.name.value') && !in_array($fieldProperty, Type::STANDARD_TYPE_NAMES)) {
                $types[] = $fieldProperty;
            }
        }

        return array_unique($types);
    }

    private function parseDefinition(string $definition): array
    {
        try {
            $parsedAST = Parser::parse($definition)->toArray();
        } catch (Exception) {
            $parsedAST = [];
        }

        return $parsedAST;
    }
}
