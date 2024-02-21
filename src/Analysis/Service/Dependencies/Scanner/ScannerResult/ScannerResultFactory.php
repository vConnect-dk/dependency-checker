<?php
declare(strict_types=1);

namespace Vconnect\IntegrityChecker\Analysis\Service\Dependencies\Scanner\ScannerResult;

class ScannerResultFactory
{
    public function create(): ScannerResultInterface
    {
        return App()->make(ScannerResultInterface::class);
    }
}