<?php
declare(strict_types=1);

namespace Vconnect\IntegrityChecker\Domain\Config\Di;

use Vconnect\IntegrityChecker\Application\Filesystem\DirectoryRegistry;
use Vconnect\IntegrityChecker\Domain\PackagesRegistry;

class PluginMap
{
    private ?array $pluginMap = null;

    public function __construct(
        private readonly PackagesRegistry $packagesRegistry
    ) {
    }

    public function getPluginMap(): array
    {
        if ($this->pluginMap === null) {
            $this->collectPluginMap();
        }

        return $this->pluginMap;
    }

    private function collectPluginMap(): void
    {
        $this->pluginMap = [];

        foreach ($this->getRootDi() as $dom) {
            $this->collectPluginsFromDom($dom);
        }

        foreach ($this->packagesRegistry->getAllPackages() as $package) {
            $configs = $package->getConfig()->getDiConfig();
            foreach ($configs as $dom) {
                $this->collectPluginsFromDom($dom);
            }
        }
    }

    private function collectPluginsFromDom(\DOMDocument $dom): void
    {
        $typeNodes = $dom->getElementsByTagName('type');
        /** @var \DOMElement $type */
        foreach ($typeNodes as $type) {
            /** @var \DOMElement $plugin */
            foreach ($type->getElementsByTagName('plugin') as $plugin) {
                $subject = trim($type->getAttribute('name'), '\\');
                $pluginType = trim($plugin->getAttribute('type'), '\\');
                $disabled = ($plugin->getAttribute('disabled') === 'true');

                if (!$disabled) {
                    $this->pluginMap[$pluginType] = $subject;
                }
            }
        }
    }

    private function getRootDi(): iterable
    {
        $iterator = new \CallbackFilterIterator(
            new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator(DirectoryRegistry::getRoot() . 'app/etc/', \FilesystemIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::SELF_FIRST
            ),
            function (\SplFileInfo $fileInfo) {
                return $fileInfo->isFile() && preg_match('/\/di.xml/i', $fileInfo->getPathname());
            }
        );

        foreach ($iterator as $fileInfo) {
            if ($fileInfo->isReadable()) {
                $content = new \DOMDocument();
                $content->loadXML($fileInfo->openFile()->fread($fileInfo->getSize()));
                yield $content;
            }
        }

        return [];
    }
}
