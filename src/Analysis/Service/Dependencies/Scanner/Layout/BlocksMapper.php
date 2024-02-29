<?php
declare(strict_types=1);

namespace Vconnect\IntegrityChecker\Analysis\Service\Dependencies\Scanner\Layout;

use Vconnect\IntegrityChecker\Domain\PackagesRegistry;

class BlocksMapper
{
    private const LAYOUT_FILE_PATTERN = /** @lang RegExp */
        '#view/(?<area>adminhtml|frontend)/layout/\w+.xml#';
    private ?array $map = null;

    public function __construct(
        private readonly PackagesRegistry $packagesRegistry
    ) {
    }

    public function getBlockDependency(string $area, string $name): array
    {
        return $this->getMap()[$area][$name] ?? [];
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
                    $this->parseLayoutBlocks(simplexml_load_file($path), $map, $area, $module);
                }

            }
        }

        return $map;
    }

    private function parseLayoutBlocks(\SimpleXMLElement $xml, array &$map, string $area, string $module): void
    {
        foreach ((array)$xml->xpath('//container | //block') as $element) {
            /** @var \SimpleXMLElement $element */
            $attributes = $element->attributes();
            $block = (string)$attributes->name;
            if (!empty($block)) {
                $map[$area][$block] = $map[$area][$block] ?? [];
                $map[$area][$block][$module] = $module;
            }
        }
    }
}