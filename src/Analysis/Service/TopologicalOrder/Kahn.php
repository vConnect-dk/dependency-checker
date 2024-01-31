<?php declare(strict_types=1);

namespace Vconnect\IntegrityChecker\Analysis\Service\TopologicalOrder;

class Kahn
{
    private array $orderedNodes = [];

    public function __construct(
        private readonly Graph $graph,
        private readonly array $whitelist
    ) {}

    public function processGraph(): void
    {
        $nodes = $this->graph->getAllNodes();

        $representation = [];
        $generation = 1;
        $queue = [];
        $nextGeneration = [];

        foreach ($nodes as $node) {
            if (in_array($node->getName(), $this->whitelist)) {
                $representation[$node->getName()] = INF;
            } else {
                $representation[$node->getName()] = count($node->getInEdges());
            }

            if ($representation[$node->getName()] === 0) {
                $queue[] = $node->getName();
            }
        }

        while (!empty($queue)) {
            while (!empty($queue)) {
                $module = array_shift($queue);
                $node = $nodes[$module];

                $this->orderedNodes[$generation][$module] = $module;

                foreach ($node->getOutEdges() as $edge) {
                    $representation[$edge] -= 1;

                    if ($representation[$edge] === 0) {
                        $nextGeneration[] = $edge;
                    }
                }
            }
            $queue = $nextGeneration;
            $generation++;
            $nextGeneration = [];
        }
    }

    public function getOrderedPackagesToRemove(): array
    {
        return $this->orderedNodes;
    }
}
