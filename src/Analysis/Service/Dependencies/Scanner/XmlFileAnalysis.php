<?php
declare(strict_types=1);

namespace Vconnect\IntegrityChecker\Analysis\Service\Dependencies\Scanner;

class XmlFileAnalysis
{
    /**
     * Analyze file to find dependencies
     *
     * @param \DOMDocument[] $xmlFilesDomDocuments
     * @param array $currentModuleNamespaces
     * @param array $nodeMap
     * @return array
     */
    public function analyze(array $xmlFilesDomDocuments, array $currentModuleNamespaces, array $nodeMap): array
    {
        $dependencies = [];
        foreach ($xmlFilesDomDocuments as $dom) {
            foreach ($nodeMap as $tagName => $attributeNames) {
                if ($tagName === XmlConfigFiles::TEXT_NODES) {
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
        $parts = explode('\\', trim($path, '\\'));
        if (count($parts) < 2) {
            return null;
        }
        $moduleNameParts = array_slice($parts, 0, 2);

        return implode('\\', $moduleNameParts);
    }
}
