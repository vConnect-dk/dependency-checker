<?php declare(strict_types=1);

namespace Vconnect\IntegrityChecker\Analysis\Service\Dependencies;

use Traversable;
use Vconnect\IntegrityChecker\Analysis\Service\Dependencies\Scanner\DbSchema;
use Vconnect\IntegrityChecker\Analysis\Service\Dependencies\Scanner\DependenciesScannerInterface;
use Vconnect\IntegrityChecker\Analysis\Service\Dependencies\Scanner\PhpFiles;
use Vconnect\IntegrityChecker\Analysis\Service\Dependencies\Scanner\QueueConfig;
use Vconnect\IntegrityChecker\Analysis\Service\Dependencies\Scanner\XmlConfigFiles;

class ScannerPool implements \IteratorAggregate
{
    /**
     * @var DependenciesScannerInterface[]
     */
    private array $scanners;

    public function __construct()
    {
        $this->scanners = [
            new PhpFiles(),
            new XmlConfigFiles(),
            new DbSchema(),
            new QueueConfig()
        ];
    }

    public function getIterator(): Traversable
    {
        yield from $this->scanners;
    }
}
