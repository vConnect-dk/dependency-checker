<?php declare(strict_types=1);

namespace Vconnect\IntegrityChecker\Domain\Package;

use Vconnect\IntegrityChecker\Domain\Package;
use Vconnect\IntegrityChecker\Domain\Package\Config\DbSchema;
use Vconnect\IntegrityChecker\Domain\Package\Config\ModuleXml;
use Vconnect\IntegrityChecker\Domain\Package\Config\Queue;
use Vconnect\IntegrityChecker\Exception\FileNotFoundException;

class Config
{
    private const DI = 'di.xml';
    private const EXT_ATR = 'etc/extension_attributes.xml';
    private const ADMIN_UI = 'etc/adminhtml/system.xml';
    private const DB_SCHEMA = 'etc/db_schema.xml';
    private const MODULE_XML = 'etc/module.xml';
    private const QUEUE_COMMUNICATION = 'etc/communication.xml';
    private const QUEUE_CONSUMER = 'etc/queue_consumer.xml';
    private const QUEUE_PUBLISHER = 'etc/queue_publisher.xml';
    private const QUEUE_TOPOLOGY = 'etc/queue_topology.xml';
    private const GRAPHQL_SCHEMA_FILE = 'etc/schema.graphqls';
    private const ROUTES_XML = 'routes.xml';

    private ?ModuleXml $moduleXml = null;
    private ?DbSchema $dbSchema = null;
    private ?array $diConfig = null;
    private ?\DOMDocument $systemXml = null;
    private ?\DOMDocument $extensionAttributes = null;
    private ?Queue $queue = null;
    private Package $package;

    public function __construct(Package $package)
    {
        $this->package = $package;
    }

    public function getDbSchema(): DbSchema
    {
        if ($this->dbSchema) {
            return $this->dbSchema;
        }

        $content = null;
        $file = $this->package->getFile(self::DB_SCHEMA);

        if ($file && $file->isReadable()) {
            $content = new \DOMDocument();
            $content->loadXML($file->openFile()->fread($file->getSize()));
        }

        $this->dbSchema = new DbSchema($content);

        return $this->dbSchema;
    }

    public function getQueueConfig(): Queue
    {
        if (!$this->queue) {
            $this->queue = new Queue(
                $this->package->getFile(self::QUEUE_COMMUNICATION),
                $this->package->getFile(self::QUEUE_CONSUMER),
                $this->package->getFile(self::QUEUE_PUBLISHER),
                $this->package->getFile(self::QUEUE_TOPOLOGY)
            );
        }

        return $this->queue;
    }

    /**
     * @return ModuleXml
     * @throws FileNotFoundException
     */
    public function getModuleXml(): ModuleXml
    {
        if ($this->moduleXml) {
            return $this->moduleXml;
        }

        $file = $this->package->getFile(self::MODULE_XML);

        if (!$file || !$file->isReadable()) {
            throw new FileNotFoundException(self::MODULE_XML, $this->package->getPath());
        }

        $this->moduleXml = new ModuleXml($file->getPathname());
        return $this->moduleXml;
    }

    /**
     * @return \DOMDocument[]
     */
    public function getDiConfig(): array
    {
        if ($this->diConfig !== null) {
            return $this->diConfig;
        }

        $this->diConfig = [];

        foreach ($this->getMultipleEtcFiles(self::DI) as $file) {
            if ($file->isReadable()) {
                $dom = new \DOMDocument();
                $dom->loadXML($file->openFile()->fread($file->getSize()));
                $this->diConfig[] = $dom;
            }
        }

        return $this->diConfig;
    }

    public function getRoutesXml(): array
    {
        return $this->getMultipleEtcFiles(self::ROUTES_XML);
    }

    public function getSystemXmlConfig(): \DOMDocument
    {
        if ($this->systemXml) {
            return $this->systemXml;
        }

        $file = $this->package->getFile(self::ADMIN_UI);
        $dom = new \DOMDocument();

        if ($file && $file->isReadable()) {
            $dom->loadXML($file->openFile()->fread($file->getSize()));
        }

        $this->systemXml = $dom;

        return $this->systemXml;
    }

    public function getExtensionAttributes(): \DOMDocument
    {
        if ($this->extensionAttributes) {
            return $this->extensionAttributes;
        }

        $file = $this->package->getFile(self::EXT_ATR);
        $dom = new \DOMDocument();

        if ($file && $file->isReadable()) {
            $dom->loadXML($file->openFile()->fread($file->getSize()));
        }
        $this->extensionAttributes = $dom;

        return $this->extensionAttributes;
    }

    public function getGraphQlSchema(): ?string
    {
        $schema = new \SplFileInfo($this->package->getPath() . '/etc/' . self::GRAPHQL_SCHEMA_FILE);
        if ($schema->isReadable()) {
            return $schema->openFile()->fread($schema->getSize());
        }

        return null;
    }

    /**
     * @param string $filename
     *
     * @return \SplFileInfo[]
     */
    private function getMultipleEtcFiles(string $filename): array
    {
        $result = [];

        foreach ($this->package->getFiles('etc') as $file) {
            if ($file->getFilename() === $filename) {
                $result[] = $file;
            }
        }

        return $result;
    }
}
