<?php
declare(strict_types=1);

namespace Vconnect\IntegrityChecker\Domain\Project\Config;

use DOMDocument;
use Vconnect\IntegrityChecker\Application\Filesystem\DirectoryRegistry;

class Root
{
    private ?array $diXml = null;

    private ?DOMDocument $dbSchema = null;

    public function getRootDiXml(): array
    {
        if (!isset($this->diXml)) {
            $this->loadRootDi();
        }

        return $this->diXml;
    }

    private function loadRootDi(): void
    {
        $this->diXml = [];

        $iterator = new \CallbackFilterIterator(
            new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator(
                    DirectoryRegistry::getRoot() . 'app/etc/',
                    \FilesystemIterator::SKIP_DOTS
                ),
                \RecursiveIteratorIterator::SELF_FIRST
            ),
            function (\SplFileInfo $fileInfo) {
                return $fileInfo->isFile() && preg_match('/\/di.xml/i', $fileInfo->getPathname());
            }
        );

        foreach ($iterator as $fileInfo) {
            if ($fileInfo->isReadable()) {
                $content = new DOMDocument();
                $content->loadXML($fileInfo->openFile()->fread($fileInfo->getSize()));
                $this->diXml[] = $content;
            }
        }
    }

    public function getRootDbSchema(): ?DOMDocument
    {
        if (!isset($this->dbSchema)) {
            $this->loaDbSchema();
        }

        return $this->dbSchema;
    }

    private function loaDbSchema(): void
    {
        $fileInfo = new \SplFileInfo(DirectoryRegistry::getRoot() . 'app/etc/db_schema.xml');

        $content = new \DOMDocument();
        if ($fileInfo->isReadable()) {
            $content->loadXML($fileInfo->openFile()->fread($fileInfo->getSize()));
        }

        $this->dbSchema = $content;
    }
}
