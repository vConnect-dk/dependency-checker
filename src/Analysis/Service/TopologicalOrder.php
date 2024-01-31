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
    private ScannerPool $scanners;

    public function __construct(private readonly array $whiteList = [])
    {
        $this->scanners = new ScannerPool();
    }

    public function analyse(iterable $packages): iterable
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

            $graph->addDependencies($package->getPackageName(), $dependencies);
        }

        $kahn = new Kahn($graph, $this->whiteList);
        $kahn->processGraph();

        return $kahn->getOrderedPackagesToRemove();
    }
}
