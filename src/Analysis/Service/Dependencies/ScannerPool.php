<?php declare(strict_types=1);

namespace Vconnect\IntegrityChecker\Analysis\Service\Dependencies;

use Traversable;
use Vconnect\IntegrityChecker\Analysis\Service\Dependencies\Scanner\DependenciesScannerInterface;

class ScannerPool implements \IteratorAggregate
{
    /**
     * @param DependenciesScannerInterface[] $scanners
     */
    public function __construct(
        private readonly array $scanners = []
    ) {
        foreach ($this->scanners as $scanner) {
            if (!$scanner instanceof DependenciesScannerInterface) {
                throw new \InvalidArgumentException('All scanners must implement DependenciesScannerInterface');
            }
        }
    }

    /**
     * @return Traversable|DependenciesScannerInterface[]
     * @noinspection PhpDocSignatureInspection
     */
    public function getIterator(): Traversable
    {
        yield from $this->scanners;
    }
}
