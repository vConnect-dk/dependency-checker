<?php

declare(strict_types=1);

namespace Vconnect\IntegrityChecker\Domain\Package\SortOrder;

use Vconnect\IntegrityChecker\Domain\Package;
use Vconnect\IntegrityChecker\Domain\PackagesRegistry;
use Vconnect\IntegrityChecker\Exception\FileNotFoundException;

class Topological
{
    public function __construct(
        private readonly PackagesRegistry $packagesRegistry
    ) {
    }

    public function getTopologicallyOrderedMagentoPackages(): array
    {
        $packages = [];
        foreach ($this->packagesRegistry->getAllPackages() as $package) {
            if (str_starts_with($package->getName(), 'magento/module')) {
                $packages[] = $package;
            }
        }

        $graph = $this->buildGraph($packages);

        return $this->orderPackages($graph);
    }

    private function orderPackages(array $graph): array
    {
        $counters = [];

        foreach ($graph as $node => $dependencies) {
            if (!isset($counters[$node])) {
                $counters[$node] = 0;
            }

            foreach ($dependencies as $dependency) {
                $counters[$dependency] = ($counters[$dependency] ?? 0) + 1;
            }
        }

        $queue = [];

        foreach ($counters as $node => $counter) {
            if ($counter === 0) {
                $queue[] = $node;
            }
        }

        $sortOrder = [];

        while ($queue) {
            $node = array_shift($queue);
            $name = $this->translatePackageName($node);
            if ($name) {
                // sort order starts from 10
                $sortOrder[$name] = count($sortOrder) + 10;
            }

            if (!isset($graph[$node])) {
                continue;
            }

            foreach ($graph[$node] as $dependency) {
                $counters[$dependency] -= 1;
                if ($counters[$dependency] === 0) {
                    $queue[] = $dependency;
                }
            }
        }

        return $sortOrder;
    }

    private function translatePackageName(string $name): ?string
    {
        return $this->packagesRegistry->getPackageNameByNamespace(str_replace('_', '\\', $name));
    }

    /**
     * @param Package[] $packages
     * @throws FileNotFoundException
     */
    private function buildGraph(array $packages): array
    {
        $graph = [];
        foreach ($packages as $package) {
            $packageName = $package->getConfig()->getModuleXml()->getModuleName();
            if (!isset($graph[$packageName])) {
                $graph[$packageName] = [];
            }

            $dependencies = $package->getModuleXmlDependencies();
            foreach ($dependencies as $dependency) {
                $graph[$packageName][] = $dependency;
            }
        }

        return $graph;
    }
}
