<?php
declare(strict_types=1);

namespace Vconnect\IntegrityChecker\Analysis\Service\Dependencies\Scanner\GraphQl;

use GraphQL\Error\SyntaxError;
use GraphQL\Language\Parser;
use Vconnect\IntegrityChecker\Domain\GraphQlSchema\GraphQlReader;

class SchemaDefinitionOwnerMapper
{
    private ?array $ownersMap = null;

    public function __construct(
        private readonly GraphQlReader $schemaReader,
    ) {
    }

    public function getOwner(string $definitionType): ?string
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
        try {
            $parsedAST = Parser::parse($definition)->toArray();
        } catch (SyntaxError) {
            $parsedAST = [];
        }

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
            foreach ($parsedAST['definitions.0.directives'] as $directive) {
                if (dot($directive)['name.value'] === 'implements') {
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
}