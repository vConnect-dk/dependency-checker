<?php declare(strict_types=1);

namespace Vconnect\IntegrityChecker\Analysis\Service;

use Vconnect\IntegrityChecker\Analysis\Service\Dependencies\Dependency;
use Vconnect\IntegrityChecker\Analysis\Service\Dependencies\ScannerPool;
use Vconnect\IntegrityChecker\Analysis\Service\TopologicalOrder\Graph;
use Vconnect\IntegrityChecker\Analysis\Service\TopologicalOrder\Kahn;
use Vconnect\IntegrityChecker\Domain\Package;
use Vconnect\IntegrityChecker\Exception\FileNotFoundException;

class TopologicalOrder implements AnalyzerInterface
{
    public function __construct(
        private readonly ScannerPool $scanners,
        private readonly array       $whiteList = [],
        private readonly ?string     $explain = null,
        private readonly bool        $useCache = true
    ) {
    }

    public function analyse(iterable $packages): iterable
    {
        $kahn = new Kahn($this->getGraph($packages), $this->whiteList);
        $kahn->processGraph();

        if ($this->explain) {
            return $kahn->explain($this->explain);
        } else {
            return $kahn->getOrderedPackagesToRemove();
        }
    }

    private function getGraph(iterable $packages): Graph
    {
        if (!$this->useCache) {
            return $this->createGraph($packages);
        }

        $cache = ROOT_DIR . DIRECTORY_SEPARATOR . 'var' . DIRECTORY_SEPARATOR . 'dependency-checker-cache.srz';

        if (is_file($cache)) {
            if (time() - filectime($cache) > 300) {
                unlink($cache);
            } else {
                return unserialize(file_get_contents($cache));
            }
        }

        $graph = $this->createGraph($packages);
        file_put_contents($cache, serialize($graph));

        return $graph;
    }


    private function createGraph(iterable $packages): Graph
    {
        $graph = new Graph();

        /** @var Package $package */
        foreach ($packages as $package) {
            $dependencyModel = new Dependency();

            foreach ($this->scanners as $scanner) {
                $dependencyModel->mergeDependencies($scanner->lookupDependencies($package));
            }

            $dependencies = $dependencyModel->getHardDependencies();
            try {
                $dependencies = array_unique(
                    array_merge(
                        $dependencies,
                        $package->getComposerRequirePackages(false),
                        $package->getComposerReplacePackages()
                    )
                );
            } catch (FileNotFoundException) {
            }

            $graph->addDependencies($package->getName(), $dependencies);
        }

        return $graph;
    }
}
