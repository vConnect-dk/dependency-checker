<?php
declare(strict_types=1);

namespace Vconnect\IntegrityChecker\Application\Observer\PhpFilesAnalysis;

use SplObjectStorage;
use Vconnect\IntegrityChecker\Application\Filesystem\Data\FileInfo;
use Vconnect\IntegrityChecker\Application\Framework\Events\ObserverInterface;
use Vconnect\IntegrityChecker\Domain\Package;

class UrlRoutesCollector implements ObserverInterface
{
    private SplObjectStorage $routesUsedPerPackage;

    public function __construct()
    {
        $this->routesUsedPerPackage = new SplObjectStorage();
    }

    public function execute(array $eventData): void
    {
        /** @var Package $package */
        $package = $eventData['package'];
        /** @var string $content */
        $content = $eventData['fileContent'];
        /** @var FileInfo $file */
        $file = $eventData['file'];

        $this->add($package, $this->getRoutesUsed($content), $file->getPathname());
    }

    public function getCollectedRoutes(Package $package): array
    {
        return $this->routesUsedPerPackage[$package] ?? [];
    }

    private function add(Package $package, array $routes, string $phpFilePath): void
    {
        $usages = $this->routesUsedPerPackage[$package] ?? [];
        foreach ($routes as $urlPath) {
            if (!isset($usages[$urlPath])) {
                $usages[$urlPath] = $phpFilePath;
            }
        }
        $this->routesUsedPerPackage[$package] = $usages;
    }

    private function getRoutesUsed(string $content): array
    {
        $pattern = /** @lang RegExp */
            '#(\->|:)(getUrl\(([\'"])(?<path>[a-zA-Z0-9\-_*/]+)\3)\s*[,)]#';
        if (!preg_match_all($pattern, $content, $matches)) {
            return [];
        }

        return $matches['path'];
    }
}