<?php declare(strict_types=1);

namespace Vconnect\IntegrityChecker\Analysis\Service\TopologicalOrder;

class Kahn
{
    private $orderedNodes = [];

    public function __construct(private Graph $graph) {}

    public function processGraph(): void
    {
        $nodes = $this->graph->getAllNodes();

        $representation = [];
        $queue = [];

        foreach ($nodes as $node) {
            $representation[$node->getName()] = count($node->getInEdges());

            if ($representation[$node->getName()] === 0) {
                $queue[] = $node->getName();
            }
        }

        while (!empty($queue)) {
            $module = array_shift($queue);
            $node = $nodes[$module];
            if ($module == 'vconnect/module-content-migration') {
                $a = 'b';
            }

            $this->orderedNodes[$module] = $module;

            foreach ($node->getOutEdges() as $edge) {
                if ($edge == 'vconnect/module-content-migration') {
                    $a = 'b';
                }
                $representation[$edge] -= 1;

                if ($representation[$edge] === 0) {
                    $queue[] = $edge;
                }
            }
        }
        $r = $this->getLeftDependencies('vaimo/composer-patches');
    }

    public function getOrderedPackagesToRemove()
    {
        return $this->orderedNodes;
    }

    public function getLeftDependencies(string $moduleName)
    {
        $module = $this->graph->getNode($moduleName);

        $result = [];
        foreach ($module->getInEdges() as $edge) {
            if (!isset($this->orderedNodes[$edge])) {
                $result[] = $edge;
            }
        }

        return $result;
    }
}
