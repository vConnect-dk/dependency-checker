<?php declare(strict_types=1);

namespace Vconnect\IntegrityChecker\Analysis\Service\TopologicalOrder;

class Node
{
    private array $in = [];
    private array $out = [];

    public function __construct(
        private readonly string $name,
        array                   $in = [],
        array                   $out = []
    ) {
        foreach ($in as $moduleName) {
            $this->in[$moduleName] = $moduleName;
        }

        foreach ($out as $moduleName) {
            $this->out[$moduleName] = $moduleName;
        }
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getInEdges(): array
    {
        return $this->in;
    }

    public function getOutEdges(): array
    {
        return $this->out;
    }

    public function addInEdge(string $dependency): void
    {
        $this->in[$dependency] = $dependency;
    }

    public function addOutEdge(string $dependency): void
    {
        $this->out[$dependency] = $dependency;
    }

    public function removeInEdge(string $dependency): void
    {
        unset($this->in[$dependency]);
    }
}
