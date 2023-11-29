<?php
declare(strict_types=1);

namespace Vconnect\IntegrityChecker\Domain\Package\Config;

class XmlDomDocuments
{
    private const XML_FILE_MASKS = ['di.xml', 'system.xml', 'extension_attributes.xml'];
    private array $packageFilesList;
    private ?array $xmlFilesDomDocument = null;
    private ?array $pluginMap = null;

    /**
     * @param array $packageFilesList
     */
    public function __construct(array $packageFilesList)
    {
        $this->packageFilesList = $packageFilesList;
    }

    /**
     * Load .xml config files.
     *
     * @param array $fileMasks - specify files for loading
     *
     * @return DOMDocument[]
     * @TODO consider loading of multiple di.xml files under different areas
     */
    public function getXmlFilesDomDocuments(array $fileMasks = self::XML_FILE_MASKS): array
    {
        if (!isset($this->xmlFilesDomDocument)) {
            $this->xmlFilesDomDocument = [];
            foreach ($this->packageFilesList as $file) {
                if (in_array($file->getFilename(), $fileMasks)) {
                    $dom = new \DOMDocument();
                    $dom->loadXML(\file_get_contents($file->getPathname()));
                    $this->xmlFilesDomDocument[$file->getFilename()][] = $dom;
                }
            }
        }

        return $this->xmlFilesDomDocument;
    }

    /**
     * @return array
     * @TODO Move to separated component, consider load of global di.xml file to the map and fact that plugins can be disabled
     */
    public function getPluginMap(): array
    {
        if (!isset($this->pluginMap)) {
            $this->pluginMap = [];
            $diDomDocuments = $this->getXmlFilesDomDocuments()['di.xml'] ?? [];
            if ($diDomDocuments) {
                foreach ($diDomDocuments as $dom) {
                    $typeNodes = $dom->getElementsByTagName('type');
                    /** @var \DOMElement $type */
                    foreach ($typeNodes as $type) {
                        /** @var \DOMElement $plugin */
                        foreach ($type->getElementsByTagName('plugin') as $plugin) {
                            $subject = trim($type->getAttribute('name'), '\\');
                            $pluginType = trim($plugin->getAttribute('type'), '\\');
                            $this->pluginMap[$pluginType] = $subject;
                        }
                    }
                }
            }
        }

        return $this->pluginMap;
    }
}
