<?php

declare(strict_types=1);

namespace Vconnect\IntegrityChecker\Analysis\Service;

use Vconnect\IntegrityChecker\Analysis\Data\Structure\Result;
use Vconnect\IntegrityChecker\Domain\Package;

class Structure implements AnalyzerInterface
{
    public function __construct(private readonly array $standardStructure = [])
    {
    }

    /**
     * Analyze packages structure and compare with standard structure.
     * For analysis, build tree of package folders and files and compare two trees.
     * Example Package Tree:
     * [
     *  'registration.php',
     *  'composer.json',
     *  'etc' => [
     *      'module.xml'
     *      ]
     * ]
     *
     *
     */
    public function analyse(iterable $packages): \Generator
    {
        /** @var Package $package */
        foreach ($packages as $package) {
            if ($package->getPackageType() !== Package::MAGENTO_PACKAGE_TYPE) {
                continue;
            }

            $tree = $package->getFilesTree()->all();

            yield new Result($package->getName(), $this->compareTrees($this->standardStructure, $tree));
        }
    }

    /**
     * Compare Standard Tree structure with extension one. Provide result as missed components.
     *
     *
     */
    private function compareTrees(array $standardTree, array $packageTree): array
    {
        $diff = [];
        foreach ($standardTree as $name => $standardStem) {
            if (!is_array($standardStem) && empty($packageTree[$standardStem])) {
                // we found a leaf - it's a file!
                $diff[] = $standardStem;
            }

            if (is_array($standardStem) && !isset($packageTree[$name])) {
                $diff[$name] = $standardStem;
            }

            if (isset($packageTree[$name]) && is_array($packageTree[$name])) {
                $result = $this->compareTrees($standardStem, $packageTree[$name]);

                if ($result !== []) {
                    $diff[$name] = $result;
                }
            }
        }

        return $diff;
    }
}
