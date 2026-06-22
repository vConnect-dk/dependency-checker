<?php

declare(strict_types=1);

namespace Vconnect\IntegrityChecker\Analysis\Service\Dependencies\Scanner\Layout;

use Vconnect\IntegrityChecker\Domain\MagentoArea;
use Vconnect\IntegrityChecker\Domain\PackagesRegistry;
use Vconnect\IntegrityChecker\Domain\Package;

class BlocksMapper
{
    private const DEFAULT_DEPENDENCY = [
        MagentoArea::AREA_FRONTEND => 'magento/module-theme',
        MagentoArea::AREA_ADMINHTML => 'magento/module-backend',
    ];

    private const LAYOUT_FILE_PATTERN = /** @lang RegExp */
        '#view/(?<area>adminhtml|frontend)/layout/\w+.xml#';
    private ?array $map = null;
    private array $layoutHandlesHierarchy = [];

    public function __construct(
        private readonly PackagesRegistry $packagesRegistry
    ) {
    }

    public function getBlockDependency(string $area, string $name, string $layoutHandle): ?string
    {
        $dependencyWithinTheSameLayout = $this->getMap()[$area][$name][$layoutHandle] ?? null;
        if ($dependencyWithinTheSameLayout === null) {
            foreach ($this->layoutHandlesHierarchy[$layoutHandle] ?? [] as $parentHandle) {
                if ($dependencyFromParentLayout = $this->getMap()[$area][$name][$parentHandle] ?? null) {
                    return $dependencyFromParentLayout;
                }
            }
        }

        return $dependencyWithinTheSameLayout;
    }

    private function getMap(): array
    {
        if ($this->map === null) {
            $this->map = $this->scanLayouts();
        }

        return $this->map;
    }

    private function scanLayouts(): array
    {
        $packages = $this->packagesRegistry->getMagentoModules();
        $map = [
            'adminhtml' => [],
            'frontend' => [],
        ];
        foreach ($packages as $package) {
            $module = $package->getName();
            foreach ($package->getFiles('view') as $file) {
                $path = $file->getPathname();
                if ($file->getExtension() === 'xml' && preg_match(self::LAYOUT_FILE_PATTERN, $path, $matches)) {
                    $area = $matches['area'];
                    $xml = simplexml_load_file($file->getPathname());
                    $layoutHandle = $file->getBasename('.xml');
                    $this->parseLayoutBlocks($xml, $layoutHandle, $map, $area, $module);
                    $this->mapLayoutHandles($xml, $layoutHandle);
                }
            }
        }

        $this->postProcessMap($map);

        return $map;
    }

    private function parseLayoutBlocks(
        \SimpleXMLElement $xml,
        string $handle,
        array &$map,
        string $area,
        string $module
    ): void {
        foreach ((array)$xml->xpath('//container | //block') as $element) {
            /** @var \SimpleXMLElement $element */
            $attributes = $element->attributes();
            $block = (string)$attributes->name;
            if ($block !== '' && $block !== '0') {
                $map[$area][$block][$handle][$module] = $module;
            }
        }
    }

    private function mapLayoutHandles(\SimpleXMLElement $xml, string $parentHandle): void
    {
        foreach ((array)$xml->xpath('//update/@handle') as $element) {
            $childHandle = (string)$element;
            $this->layoutHandlesHierarchy[$childHandle][] = $parentHandle;
        }
    }

    private function postProcessMap(array &$map): void
    {
        foreach ($map as $area => $blocks) {
            foreach ($blocks as $block => $layoutHandles) {
                foreach ($layoutHandles as $layoutHandle => $modules) {
                    $map[$area][$block][$layoutHandle] = count($modules) > 1
                        ? $this->reduceDependencies($area, $modules)
                        : current($modules);
                }
            }
        }
    }

    private function reduceDependencies(string $area, array $modules): string
    {
        if (isset($modules[self::DEFAULT_DEPENDENCY[$area]])) {
            return self::DEFAULT_DEPENDENCY[$area];
        }

        $modulesDependencies = [];
        foreach (array_keys($modules) as $module) {
            $package = $this->packagesRegistry->getPackage($module);
            $modulesDependencies[$module] = array_unique(
                array_merge(
                    $this->getModuleXmlDependencies($package),
                    $package->getComposerRequirePackages(false)
                )
            );
        }

        uasort($modulesDependencies, fn ($a, $b): int => count($a) <=> count($b));

        foreach ($modulesDependencies as $module => $dependencyList) {
            foreach ($dependencyList as $dependency) {
                if (isset($modules[$dependency])) {
                    unset($modules[$module]);
                }
            }
        }

        $magentoOnlyModules = array_filter(
            $modules,
            fn (string $module): bool => str_starts_with($module, 'magento/')
        ) ?: null;
        $deps = $magentoOnlyModules ?? $modules;

        return current($deps);
    }

    private function getModuleXmlDependencies(?Package $package): array
    {
        return array_map(
            fn (string $moduleName): ?string => $this->packagesRegistry->getPackageNameByNamespace(
                str_replace('_', '\\', $moduleName)
            ),
            $package->getModuleXmlDependencies()
        );
    }
}
