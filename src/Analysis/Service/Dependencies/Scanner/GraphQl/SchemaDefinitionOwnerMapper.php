<?php
declare(strict_types=1);

namespace Vconnect\IntegrityChecker\Analysis\Service\Dependencies\Scanner\GraphQl;

use Exception;
use GraphQL\Language\Parser;
use GraphQL\Type\Definition\Type;
use Vconnect\IntegrityChecker\Domain\GraphQlSchema\GraphQlReader;

class SchemaDefinitionOwnerMapper
{
    private ?array $ownersMap = null;

    public function __construct(
        private readonly GraphQlReader $schemaReader,
    ) {
    }

    /**
     *  We treat extending types as soft dependencies.
     * @param string $definitionType
     * @return string|null
     */
    public function getSoftDependency(string $definitionType): ?string
    {
        return $this->getOwner($definitionType);
    }

    /**
     * Implementing interfaces, using types for fields we treat as hard dependencies.
     *
     * @param string $definition
     * @return array
     */
    public function getHardDependencies(string $definition): array
    {
        $typesUsed = $this->getTypesUsedInDefinition($definition);

        return array_map(fn(string $type) => $this->getOwner($type), $typesUsed);
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
            foreach ($definitions as $package => $definition) {
                $ownersMapping[$type][$this->prioritizeCandidate($definition, $package)] = $package;
            }
            ksort($ownersMapping[$type]);
            $ownersMapping[$type] = current($ownersMapping[$type]) ?? null;
        }

        return $ownersMapping;
    }

    private function prioritizeCandidate(
        string $definition,
        string $package
    ): ?int {
        $parsedAST = $this->parseDefinition($definition);

        foreach ($this->getTypeOwnerPriorityRules() as $priority => $rule) {
            if ($rule($package, $parsedAST)) {
                return $priority;
            }
        }

        return null;
    }

    /**
     * @return callable[]
     */
    private function getTypeOwnerPriorityRules(): array
    {
        return [
            0 => [$this, 'isInterfaceWithTypeResolver'],
            1 => [$this, 'hasInterfaceImplementation'],
            2 => fn(string $package, array $parsedAST) => $this->isMagentoCoreType($package, $parsedAST)
                && $this->hasDescription($package, $parsedAST),
            3 => [$this, 'isMagentoCoreType'],
            4 => [$this, 'hasDescription'],
            5 => fn(string $package, array $parsedAST) => true
        ];
    }

    private function isInterfaceWithTypeResolver(string $package, array $parsedAST): bool
    {
        $parsedAST = dot($parsedAST);
        $isInterface = $parsedAST['definitions.0.kind'] === 'InterfaceTypeDefinition';
        if ($isInterface) {
            foreach ($parsedAST['definitions.0.directives'] as $directive) {
                if (dot($directive)['name.value'] === 'typeResolver') {
                    return true;
                }
            }
        }

        return false;
    }

    private function hasInterfaceImplementation(string $package, array $parsedAST): bool
    {
        $parsedAST = dot($parsedAST);
        if ($parsedAST['definitions.0.kind'] === 'ObjectTypeDefinition') {
            foreach ($parsedAST['definitions.0.interfaces'] as $interface) {
                if (dot($interface)['name.value']) {
                    return true;
                }
            }
        }

        return false;
    }

    private function hasDescription(string $package, array $parsedAST): bool
    {
        $parsedAST = dot($parsedAST);
        foreach ($parsedAST->get('definitions.0.directives', []) as $directive) {
            $directive = dot($directive);
            if ($directive['arguments.0.name.value'] === 'description' && $directive['arguments.0.value.value']) {
                return true;
            }
        }

        return false;
    }

    private function isMagentoCoreType(string $package, array $parsedAST = null): bool
    {
        return str_starts_with($package, 'magento/module');
    }

    private function getTypesUsedInDefinition(string $definition): array
    {
        $types = [];
        $ast = dot($this->parseDefinition($definition)['definitions'][0] ?? []);
        foreach ($ast->get('interfaces', []) as $interface) {
            $types[] = $interface['name']['value'];
        }

        foreach (dot($ast->get('fields', []))->flatten() as $flattenKey => $fieldProperty) {
            if (str_ends_with($flattenKey, '.type.name.value') && !in_array($fieldProperty, Type::STANDARD_TYPE_NAMES)) {
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