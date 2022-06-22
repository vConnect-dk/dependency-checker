<?php
declare(strict_types=1);

namespace Vconnect\IntegrityChecker\Analysis\Service\Dependencies\Scanner\ScannerResult;

class PhpFilesScannerResult implements ScannerResultInterface
{
    private array $softDependencies = [];
    private array $hardDependencies = [];

    public function setSoftDependencies(array $dependencies): void
    {
        $this->softDependencies = $dependencies;
    }
    public function setHardDependencies(array $dependencies): void
    {
        $this->hardDependencies = $dependencies;
    }
    public function getSoftDependencies(): array
    {
        return $this->softDependencies;
    }
    public function getHardDependencies(): array
    {
        return $this->hardDependencies;
    }
}
