<?php
declare(strict_types=1);

namespace Vconnect\IntegrityChecker\Analysis\Service\Dependencies\Scanner\Layout;

use Vconnect\IntegrityChecker\Analysis\Service\Dependencies\DependencyInterface;
use Vconnect\IntegrityChecker\Analysis\Service\Dependencies\Scanner\Routes\RouteMapper;
use Vconnect\IntegrityChecker\Application\Filesystem\Data\FileInfo;
use Vconnect\IntegrityChecker\Domain\PackagesRegistry;

class LayoutFileScanner
{
    private ?string $namespaces = null;

    public function __construct(
        private readonly PackagesRegistry $packagesRegistry,
        private readonly RouteMapper      $routeMapper,
        private readonly BlocksMapper     $blocksMapper
    ) {
    }

    public function getDependenciesFromLayoutFile(FileInfo $file, string $area): array
    {
        $contents = $file->getContents();

        $deps = [DependencyInterface::TYPE_SOFT => [], DependencyInterface::TYPE_HARD => []];
        $layoutHandle = $file->getBasename('.xml');
        $xml = simplexml_load_string($contents);

        $this->getLayoutDependencyFromFileName($deps, $layoutHandle, $area);
        $this->getBlockClassAndTemplateDeps($deps, $contents);
        $this->getLayoutHandleUpdateDeps($deps, $xml, $area);
        $this->getReferenceDependencies($deps, $xml, $area, $layoutHandle);

        return [
            array_keys($deps[DependencyInterface::TYPE_SOFT]),
            array_keys($deps[DependencyInterface::TYPE_HARD])
        ];
    }

    /**
     * Check dependencies for <block> element
     *
     * Ex.: <block class="{name}">
     *      <block template="{path}">
     *
     * @param array $deps
     * @param string $contents
     * @return void
     */
    private function getBlockClassAndTemplateDeps(array &$deps, string $contents): void
    {
        $patterns = [
            '/<(?:referenceBlock|block)(?:[^>])*class\s*=\s*[\'"](?<namespace>' .
            $this->getNamespaces() .
            ')[_\\\\]' .
            '(?<module>[A-Z][a-zA-Z]+)[_\\\\]/' => DependencyInterface::TYPE_HARD,
            '/<(?:referenceBlock|block)(?:[^>])*template\s*=\s*[\'"](?<namespace>' .
            $this->getNamespaces() .
            ')[_\\\\]' .
            '(?<module>[A-Z][a-zA-Z]+)::[\w\/\.]+[\'"].*/' => DependencyInterface::TYPE_SOFT,
        ];

        $this->extractDependenciesByRegexp($deps, $contents, $patterns);
    }

    private function getNamespaces(): string
    {
        if ($this->namespaces === null) {
            $namespaces = $this->packagesRegistry->getAllProjectNamespaces();
            $availableVendors = [];

            foreach ($namespaces as $namespace) {
                $availableVendors[] = explode('\\', $namespace)[0];
            }
            $this->namespaces = implode('|', array_unique($availableVendors));
        }

        return $this->namespaces;
    }

    /**
     * Check layout handles updates
     *
     * Ex.: <update handle="{name}" />
     *
     * @param array $deps
     * @param \SimpleXMLElement $xml
     * @param string $area
     * @return void
     */
    private function getLayoutHandleUpdateDeps(array &$deps, \SimpleXMLElement $xml, string $area): void
    {
        foreach ((array)$xml->xpath('//update/@handle') as $element) {
            $dependency = $this->getLayoutHandleDependency($area, (string)$element);
            if ($dependency) {
                $deps[DependencyInterface::TYPE_SOFT][$dependency] = true;
            }
        }
    }

    /**
     * Check layout references
     *
     * Ex.: <referenceBlock name="{name}">
     *
     * @param array $deps
     * @param \SimpleXMLElement $xml
     * @param string $area
     * @param string $layoutHandle
     * @return void
     */
    protected function getReferenceDependencies(array &$deps, \SimpleXMLElement $xml, string $area, string $layoutHandle): void
    {
        foreach ((array)$xml->xpath('//referenceBlock/@name | //referenceContainer/@name') as $element) {
            $blockDependency = $this->blocksMapper->getBlockDependency($area, (string)$element, $layoutHandle);
            if ($blockDependency) {
                $deps[DependencyInterface::TYPE_SOFT][$blockDependency] = true;
            }
        }
    }

    private function extractDependenciesByRegexp(array &$deps, $contents, $patterns = []): void
    {
        foreach ($patterns as $pattern => $type) {
            if (preg_match_all($pattern, $contents, $matches, PREG_SET_ORDER)) {
                foreach ($matches as $match) {
                    $namespace = $match['namespace'] . '\\' . $match['module'];
                    $packageName = $this->packagesRegistry->getPackageNameByNamespace($namespace);
                    if ($packageName) {
                        $deps[$type][$packageName] = true;
                    }
                }
            }
        }
    }

    private function getLayoutHandleDependency($area, $handle): ?string
    {
        $routePath = str_replace('_', '/', $handle);
        return $this->routeMapper->getDependencyByRouteParams($routePath, $area);
    }

    private function getLayoutDependencyFromFileName(array &$deps, string $basename, string $area): void
    {
        $parts = explode('_', $basename, 3);
        $originalRoute = implode('/', $parts);
        $reducedRoute = implode('/', array_slice($parts, 0, count($parts) - 1));
        $dependency = $this->routeMapper->getDependencyByRouteParams($originalRoute, $area)
            ?: $this->routeMapper->getDependencyByRouteParams($reducedRoute, $area);
        if ($dependency) {
            $deps[DependencyInterface::TYPE_SOFT][$dependency] = true;
        }
    }
}