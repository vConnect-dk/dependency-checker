<?php
declare(strict_types=1);

namespace Vconnect\IntegrityChecker\Analysis\Service\Dependencies\Scanner\ScannerResult;

use DI\FactoryInterface;

class ScannerResultFactory
{
    public function __construct(
        private readonly FactoryInterface $factory
    ) {
    }

    public function create(): ScannerResultInterface
    {
        return $this->factory->make(ScannerResultInterface::class);
    }
}