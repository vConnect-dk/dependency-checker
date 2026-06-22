<?php

declare(strict_types=1);

namespace Vconnect\IntegrityChecker\Analysis\Service\Dependencies\Scanner\XmlConfigFiles;

use Vconnect\IntegrityChecker\Domain\Package;
use Vconnect\IntegrityChecker\Domain\PackagesRegistry;

class XmlFileAnalysis
{
    public function __construct(
        private readonly PackagesRegistry $packagesRegistry
    ) {
    }

    public const TEXT_NODES = 'textNodes';

    /**
     * Analyze file to find dependencies
     *
     * @return string[]
     */
    public function analyze(Package $package, array $nodeMap): array
    {
        $dependencies = [];
        $config = $package->getConfig()->getDiConfig();

        foreach ($config as $item) {
            $dependencies[] = $this->analyzeConfig($package->getPackageNamespaces(), $nodeMap, $item);
        }

        $config = $package->getConfig()->getSystemXmlConfig();
        if ($config->childElementCount) {
            $dependencies[] = $this->analyzeConfig($package->getPackageNamespaces(), $nodeMap, $config);
        }

        $config = $package->getConfig()->getExtensionAttributes();
        if ($config->childElementCount) {
            $dependencies[] = $this->analyzeConfig($package->getPackageNamespaces(), $nodeMap, $config);
        }

        return array_merge(...$dependencies);
    }

    public function analyzeConfig(array $currentModuleNamespaces, array $nodeMap, \DOMDocument $dom): array
    {
        $dependencies = [];

        foreach ($nodeMap as $tagName => $attributeNames) {
            if ($tagName === self::TEXT_NODES) {
                $dependencies = array_merge(
                    $this->getDependenciesByTextNodes($dom, $attributeNames, $currentModuleNamespaces),
                    $dependencies
                );
                continue;
            }
            $nodes = $dom->getElementsByTagName($tagName);
            /** @var \DOMElement $node */
            foreach ($nodes as $node) {
                foreach ($attributeNames as $attributeName) {
                    $referenceModule = $this->getModuleNamespace($node->getAttribute($attributeName));
                    if (!$referenceModule) {
                        continue;
                    }
                    if (\in_array($referenceModule, $currentModuleNamespaces)) {
                        continue;
                    }
                    if (!($dependency = $this->packagesRegistry->getPackageNameByNamespace($referenceModule))) {
                        continue;
                    }
                    $dependencies[] = $dependency;
                }
            }
        }

        return $dependencies;
    }

    private function getDependenciesByTextNodes(
        \DOMDocument $dom,
        array        $expressions,
        array        $currentModuleNamespaces
    ): array {
        $dependencies = [];
        foreach ($expressions as $expression) {
            $xpath = new \DOMXPath($dom);
            $textNodes = $xpath->query($expression);
            /** @var \DOMElement $node */
            foreach ($textNodes as $node) {
                $referenceModule = $this->getModuleNamespace($node->nodeValue);
                if (!$referenceModule) {
                    continue;
                }
                if (\in_array($referenceModule, $currentModuleNamespaces)) {
                    continue;
                }
                if (!($dependency = $this->packagesRegistry->getPackageNameByNamespace($referenceModule))) {
                    continue;
                }

                $dependencies[] = $dependency;
            }
        }

        return $dependencies;
    }

    /**
     * Extract Vendor\Module namespace prefix from a fully-qualified class/interface path.
     */
    public function getModuleNamespace(string $path): ?string
    {
        $parts = explode('\\', trim($path, "\\\t\n\r\0\x0B "));
        if (count($parts) < 2) {
            return null;
        }
        $moduleNameParts = array_slice($parts, 0, 2);

        return implode('\\', $moduleNameParts);
    }
}
