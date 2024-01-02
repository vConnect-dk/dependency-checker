<?php
declare(strict_types=1);

namespace Vconnect\IntegrityChecker\Analysis\Service\Dependencies\Scanner\ScannerResult;

class ScannerResult implements ScannerResultInterface
{
    private array $softDependencies = [];
    private array $hardDependencies = [];

    /**
     * @param string[] $dependencies
     * @return void
     */
    public function addSoftDependencies(array $dependencies): void
    {
        $this->softDependencies = array_unique(array_merge($this->softDependencies, $dependencies));
    }

    /**
     * @param string[] $dependencies
     * @return void
     */
    public function addHardDependencies(array $dependencies): void
    {
        $this->hardDependencies = array_unique(array_merge($this->hardDependencies, $dependencies));
    }

    /**
     * @return string[]
     */
    public function getSoftDependencies(): array
    {
        return $this->softDependencies;
    }

    /**
     * @return string[]
     */
    public function getHardDependencies(): array
    {
        return $this->hardDependencies;
    }
}
