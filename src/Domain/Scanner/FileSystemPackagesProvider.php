<?php declare(strict_types=1);

namespace Vconnect\IntegrityChecker\Domain\Scanner;

use DI\FactoryInterface;
use Vconnect\IntegrityChecker\Application\Filesystem\DirectoryRegistry;
use Vconnect\IntegrityChecker\Domain\Package;

class FileSystemPackagesProvider
{
    public function __construct(
        private readonly FactoryInterface $factory
    ) {
    }

    public function getPackagesRecursively(
        array $paths,
        ?callable $filter = null,
        string $fileMask = '/composer\\.json/'
    ): \Generator {
        $collectedPaths = [];
        $paths = array_unique($paths);
        foreach ($paths as $path) {
            if (is_dir(DirectoryRegistry::getRoot() . $path)) {
                $collectedPaths[] = $this->getMatchedFilesFolders(DirectoryRegistry::getRoot() . $path, $fileMask, $filter);
            }
        }

        $uniquePackages = array_unique(array_merge([], ...$collectedPaths));

        foreach ($uniquePackages as $packagePath) {
            yield $this->factory->make(Package::class, ['path' => $packagePath]);
        }
    }

    public function getPackagesByDirectPath(array $paths, string $rootPackageFileName = 'composer.json'): \Generator
    {
        $collectedPaths = [];
        $paths = array_unique($paths);
        foreach ($paths as $path) {
            /* @phpstan-ignore-next-line */
            $file = new \SplFileInfo(DirectoryRegistry::getRoot() . $path . DIRECTORY_SEPARATOR . $rootPackageFileName);

            if ($file->isFile()) {
                $collectedPaths[] = dirname($file->getRealPath());
            }
        }

        foreach (array_unique($collectedPaths) as $packagePath) {
            yield $this->factory->make(Package::class, ['path' => $packagePath]);
        }
    }

    /**
     * Lookup and get directory path to composer.json files.
     *
     * @param string $path
     * @param string $fileMask
     * @param callable|null $filter
     *
     * @return array
     */
    private function getMatchedFilesFolders(string $path, string $fileMask, ?callable $filter): array
    {
        $allFiles = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($path));
        $matchedFiles = new \RegexIterator($allFiles, $fileMask);

        $matchedFiles = iterator_to_array($matchedFiles);

        if ($filter !== null) {
            $matchedFiles = array_filter($matchedFiles, $filter);
        }

        return array_map(fn(\SplFileInfo $file) => dirname($file->getRealPath()), $matchedFiles);
    }
}
