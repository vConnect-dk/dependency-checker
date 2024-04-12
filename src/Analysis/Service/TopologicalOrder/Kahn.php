<?php declare(strict_types=1);

namespace Vconnect\IntegrityChecker\Analysis\Service\TopologicalOrder;

class Kahn
{
    private array $orderedNodes = [];

    public function __construct(
        private readonly Graph $graph,
        private readonly array $whitelist = [],
        private readonly array $nonMagento = []
    ) {}

    public function processGraph(): void
    {
        $nodes = $this->graph->getAllNodes();

        $representation = [];
        $generation = 1;
        $queue = [];
        $nextGeneration = [];
        $notRemovable = array_merge($this->whitelist, $this->nonMagento);

        foreach ($nodes as $node) {
            if (isset($notRemovable[$node->getName()])) {
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

    public function explain(string $moduleName): array
    {
        if (!isset($this->graph->getAllNodes()[$moduleName])) {
            return [[
                'message' => sprintf('Extension "%s" can not be found in the project codebase.', $moduleName),
                'problem' => true
            ]];
        }

        $orderedNodes = [];
        foreach ($this->orderedNodes as $generationNodes) {
            $orderedNodes = array_merge($orderedNodes, $generationNodes);
        }

        if (isset($orderedNodes[$moduleName])) {
            return $this->nodeRemoveSequences($moduleName);
        }


        return $this->analyseNotRemovableNode($moduleName, $orderedNodes);
    }

    private function analyseNotRemovableNode(string $moduleName, array $orderedNodes): array
    {
        $results = [];
        $nodes = $this->graph->getAllNodes();
        $visited = [];
        $whitelist = $this->whitelist;
        $notMagento = $this->nonMagento;
        $finished = [];

        $dfs = function(Node $node, $num, $prefix) use (
            &$visited,
            &$finished,
            &$results,
            $whitelist,
            $notMagento,
            $orderedNodes,
            $nodes,
            &$dfs
        ) {
            $prefix .= '-' . $num;

            if (isset($finished[$node->getName()])) {
                return;
            }

            if (isset($orderedNodes[$node->getName()])) {
                $results[] = [
                    'message' => sprintf('%s| Extension "%s" can be removed', $prefix, $node->getName()),
                    'problem' => false
                ];
                return;
            }

            if (isset($whitelist[$node->getName()])) {
                $results[] = [
                    'message' => sprintf(
                        '%s| Extension "%s" can not be removed because it is whitelisted',
                        $prefix,
                        $node->getName()
                    ),
                    'problem' => true
                ];
                return;
            }

            if (isset($notMagento[$node->getName()])) {
                $results[] = [
                    'message' => sprintf(
                        '%s| Extension "%s" can not be removed because it is not Magento 2 extension',
                        $prefix,
                        $node->getName()
                    ),
                    'problem' => true
                ];
                return;
            }


            if (isset($visited[$node->getName()])) {
                $results[] = [
                    'message' => sprintf(
                        '%s| Cycle detected. Extension "%s" can not be removed because of the cycle',
                        $prefix,
                        $node->getName()
                    ),
                    'problem' => true
                ];
                return;
            }

            $results[] = [
                'message' => sprintf(
                    '%s| Extension "%s" can not be removed due to dependencies.',
                    $prefix,
                    $node->getName()
                ),
                'problem' => true
            ];

            $visited[$node->getName()] = true;
            foreach ($node->getInEdges() as $edge) {
                $dfs($nodes[$edge], $num++, $prefix);
            }
            $finished[$node->getName()] = true;
        };

        $dfs($nodes[$moduleName], 1, '|');

        return $results;
    }


    /**
     * Run BFS to showcase the roadmap how extension can be removed
     *
     * @param string $moduleName
     *
     * @return array
     */
    private function nodeRemoveSequences(string $moduleName): array
    {
        $roadmap = [$moduleName => $moduleName];
        $nodes = $this->graph->getAllNodes();
        $queue = [$nodes[$moduleName]];

        while (!empty($queue)) {
            /** @var Node $node */
            $node = array_shift($queue);
            foreach ($node->getInEdges() as $edge) {
                if (!isset($roadmap[$edge])) {
                    $roadmap[$edge] = $edge;
                    $queue[] = $nodes[$edge];
                }
            }
        }

        $result = [
            [
                'message' => sprintf('Extension "%s" can be removed.', $moduleName),
                'problem' => false
            ]
        ];

        foreach ($roadmap as $dep) {
            $result[] = [
                'message' => sprintf('"%s": "*",', $dep),
                'problem' => false
            ];
        }

        return $result;
    }
}
