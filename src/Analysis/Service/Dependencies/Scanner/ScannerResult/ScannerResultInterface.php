<?php
declare(strict_types=1);

namespace Vconnect\IntegrityChecker\Analysis\Service\Dependencies\Scanner\ScannerResult;

interface ScannerResultInterface
{
    public function setSoftDependencies(array $dependencies): void;
    public function setHardDependencies(array $dependencies): void;
    public function getSoftDependencies(): array;
    public function getHardDependencies(): array;

}
