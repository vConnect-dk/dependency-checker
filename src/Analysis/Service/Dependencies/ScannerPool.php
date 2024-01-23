<?php
declare(strict_types=1);

namespace Vconnect\IntegrityChecker\Analysis\Service\Dependencies;

use Vconnect\IntegrityChecker\Analysis\Service\Dependencies\Scanner\DbSchema;
use Vconnect\IntegrityChecker\Analysis\Service\Dependencies\Scanner\DependenciesScannerInterface;
use Vconnect\IntegrityChecker\Analysis\Service\Dependencies\Scanner\PhpFiles;
use Vconnect\IntegrityChecker\Analysis\Service\Dependencies\Scanner\QueueConfig;
use Vconnect\IntegrityChecker\Analysis\Service\Dependencies\Scanner\XmlConfigFiles;

class ScannerPool implements \Iterator
{
    private int $position = 0;

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

    public function current(): DependenciesScannerInterface
    {
        return $this->scanners[$this->position];
    }

    public function next(): void
    {
        $this->position++;
    }

    public function key(): int
    {
        return $this->position;
    }

    public function valid(): bool
    {
        return isset($this->scanners[$this->position]);
    }

    public function rewind(): void
    {
        $this->position = 0;
    }
}
