<?php
declare(strict_types=1);

namespace Vconnect\IntegrityChecker\Analysis\Service\Dependencies\Scanner\XmlConfigFiles;

use Vconnect\IntegrityChecker\Domain\Package;

class XmlFileAnalysis
{
    public const TEXT_NODES = 'textNodes';

    /**
     * Analyze file to find dependencies
     *
     * @param \DOMDocument[] $xmlFilesDomDocuments
     * @param array $currentModuleNamespaces
     * @param array $nodeMap
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
                    if (!$referenceModule || \in_array($referenceModule, $currentModuleNamespaces)) {
                        continue;
                    }
                    $dependencies[] = $referenceModule;
                }
            }
        }

        return $dependencies;
    }

    /**
     * @param \DOMDocument $dom
     * @param array $expressions
     * @param array $currentModuleNamespaces
     * @return array
     */
    private function getDependenciesByTextNodes(
        \DOMDocument $dom,
        array $expressions,
        array $currentModuleNamespaces
    ): array {
        $dependencies = [];
        foreach ($expressions as $expression) {
            $xpath = new \DOMXPath($dom);
            $textNodes = $xpath->query($expression);
            /** @var \DOMElement $node */
            foreach ($textNodes as $node) {
                $referenceModule = $this->getModuleNamespace($node->nodeValue);
                if (!$referenceModule || \in_array($referenceModule, $currentModuleNamespaces)) {
                    continue;
                }
                $dependencies[] = $referenceModule;
            }
        }

        return $dependencies;
    }

    /**
     * @param string $path
     * @return string|null
     */
    private function getModuleNamespace(string $path): ?string
    {
        $parts = explode('\\', trim($path, "\\\t\n\r\0\x0B "));
        if (count($parts) < 2) {
            return null;
        }
        $moduleNameParts = array_slice($parts, 0, 2);

        return implode('\\', $moduleNameParts);
    }
}
