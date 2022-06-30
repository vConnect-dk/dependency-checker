<?php
declare(strict_types=1);

namespace Vconnect\IntegrityChecker\Analysis\Service\Dependencies\Scanner;

use Vconnect\IntegrityChecker\Analysis\Service\Dependencies\Model\DependencyInterface;

class XmlFileAnalysis
{
    private const TEXT_NODES = 'textNodes';
    /**
     * Array of nodes for DOMDocument to specify dependencies as 'soft'
     */
    private const NODE_MAP_SOFT_DEPENDENCY = [
        'type' => ['name'],
        'preference' => [
            'type',
            'for'
        ],
        'plugin' => ['type'],
        'virtualType' => ['type'],
    ];

    /**
     * Array of nodes for DOMDocument to specify dependencies as 'hard'
     */
    private const NODE_MAP_HARD_DEPENDENCY = [
        'extension_attributes' => ['for'],
        'attribute' => ['type'],
        self::TEXT_NODES => ['//*[@xsi:type="object"]']
    ];

    /**
     * Get array of dependencies that are specified as 'soft' and 'hard'
     *
     * @param \SplFileInfo $file
     * @param array $currentModuleNamespaces
     * @return array
     */
    public function getDependencies(\SplFileInfo $file, array $currentModuleNamespaces): array
    {
        return [
            DependencyInterface::TYPE_SOFT =>
                $this->analyzeFile($file, $currentModuleNamespaces, self::NODE_MAP_SOFT_DEPENDENCY),
            DependencyInterface::TYPE_HARD =>
                $this->analyzeFile($file, $currentModuleNamespaces, self::NODE_MAP_HARD_DEPENDENCY)
        ];
    }

    /**
     * Analyze file to find dependencies
     *
     * @param \SplFileInfo $file
     * @param array $currentModuleNamespaces
     * @param array $nodeMap
     * @return array
     */
    private function analyzeFile(\SplFileInfo $file, array $currentModuleNamespaces, array $nodeMap): array
    {
        $dependencies = [];
        $dom = new \DOMDocument();
        $dom->loadXML(\file_get_contents($file->getPathname()));
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
        $parts = explode('\\', trim($path, '\\'));
        if (count($parts) < 2) {
            return null;
        }
        $moduleNameParts = array_slice($parts, 0, 2);

        return implode('\\', $moduleNameParts);
    }
}
