<?php
declare(strict_types=1);

namespace Vconnect\IntegrityChecker\Domain\Package\DiXml;

class DiXml
{
    private array $paths;
    private ?array $content = null;
    private ?array $pluginMap = null;

    /**
     * @param array $paths
     */
    public function __construct(array $paths)
    {
        $this->paths = $paths;
        $this->parseDiXmlFiles();
    }

    /**
     * @return array|null
     */
    public function getPluginMap(): ?array
    {
        if (!$this->pluginMap) {
            $this->setPluginMap();
        }

        return $this->pluginMap;
    }

    /**
     * @return void
     */
    private function parseDiXmlFiles(): void
    {
        foreach ($this->paths as $path) {
            $dom = new \DOMDocument();
            $dom->loadXML(\file_get_contents($path));
            $this->content[] = $dom;
        }
    }

    /**
     * @return void
     */
    private function setPluginMap(): void
    {
        foreach ($this->content as $dom) {
            $typeNodes = $dom->getElementsByTagName('type');
            /** @var \DOMElement $type */
            foreach ($typeNodes as $type) {
                /** @var \DOMElement $plugin */
                foreach ($type->getElementsByTagName('plugin') as $plugin) {
                    $subject = $type->getAttribute('name');
                    $pluginType = $plugin->getAttribute('type');
                    $this->pluginMap[$pluginType] = $subject;
                }
            }
        }
//        $result = [];
//        foreach (self::$tagNameMap as $tagName => $attributeNames) {
//            foreach ($this->content as $dom) {
//                $nodes = $dom->getElementsByTagName($tagName);
//                /** @var \DOMElement $node */
//                foreach ($nodes as $node) {
//
//                    foreach ($attributeNames as $attributeName) {
//                        $result[] = $node->getAttribute($attributeName);
//                    }
//                }
//            }
//        }
    }

//    private static $tagNameMap = [
//        'type' => ['name'],
//        'preference' => [
//            'type',
//            'for'
//        ],
//        'plugin' => ['type'],
//        'virtualType' => ['type']
//    ];
}
