<?php declare(strict_types=1);

namespace Vconnect\IntegrityChecker\Analysis\Service;

use RectorPrefix202304\Symfony\Component\Filesystem\Exception\FileNotFoundException;
use Vconnect\IntegrityChecker\Analysis\Service\Dependencies\Dependency;
use Vconnect\IntegrityChecker\Analysis\Service\Dependencies\Scanner\DbSchema;
use Vconnect\IntegrityChecker\Analysis\Service\Dependencies\Scanner\PhpFiles;
use Vconnect\IntegrityChecker\Analysis\Service\Dependencies\Scanner\XmlConfigFiles;
use Vconnect\IntegrityChecker\Analysis\Service\Dependencies\ScannerPool;
use Vconnect\IntegrityChecker\Analysis\Service\TopologicalOrder\Graph;
use Vconnect\IntegrityChecker\Analysis\Service\TopologicalOrder\Kahn;
use Vconnect\IntegrityChecker\Domain\Package;

class TopologicalOrder implements AnalyzerInterface
{
    private ScannerPool $scanners;

    public function __construct()
    {
        $this->scanners = new ScannerPool();
    }

    public function analyse(iterable $packages): iterable
    {

        if (is_file('serialized.srz')) {
            $graph = unserialize(file_get_contents('serialized.srz'));
        } else {

            $graph = new Graph();

            /** @var Package $package */
            foreach ($packages as $package) {
                $dependencyModel = new Dependency();

                foreach ($this->scanners as $scanner) {
                    $dependencyModel->mergeDependencies($scanner->lookupDependencies($package));
                }

                if ($package->getPackageName() !== 'magento/magento2-base') {

                $dependencies = $dependencyModel->getHardDependencies();
                    try {
                        $dependencies = array_unique(
                            array_merge($dependencies, $package->getComposerRequirePackages(false))
                        );
                    } catch (FileNotFoundException) {
                    }
                }

                $graph->addDependencies($package->getPackageName(), $dependencies);
                //              $graph->addDependencies($package->getPackageName(), $package->getComposerRequirePackages());

            }
        }

        file_put_contents('serialized.srz', serialize($graph));

        $kahn = new Kahn($graph);
        $kahn->processGraph();

        return $kahn->getOrderedPackagesToRemove();
    }
}
