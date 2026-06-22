<?php

declare(strict_types=1);

namespace Vconnect\IntegrityChecker\Analysis\Service\TopologicalOrder;

class Graph
{
    /** @var Node[] */
    private array $nodes = [];

    public function addDependencies(string $moduleName, array $dependencies): void
    {
        if (!isset($this->nodes[$moduleName])) {
            $this->nodes[$moduleName] = new Node($moduleName);
        }

        foreach ($dependencies as $dependency) {
            $this->nodes[$moduleName]->addOutEdge($dependency);
            if (!isset($this->nodes[$dependency])) {
                $this->nodes[$dependency] = new Node($dependency);
            }

            $this->nodes[$dependency]->addInEdge($moduleName);
        }
    }

    public function getNode(string $moduleName): ?Node
    {
        return $this->nodes[$moduleName] ?? null;
    }

    public function getAllNodes(): array
    {
        return $this->nodes;
    }
}
