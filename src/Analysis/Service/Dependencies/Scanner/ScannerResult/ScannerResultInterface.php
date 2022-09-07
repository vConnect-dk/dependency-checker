<?php
declare(strict_types=1);

namespace Vconnect\IntegrityChecker\Analysis\Service\Dependencies\Scanner\ScannerResult;

interface ScannerResultInterface
{
    /**
     * @param string[] $dependencies
     * @return void
     */
    public function setSoftDependencies(array $dependencies): void;

    /**
     * @param string[] $dependencies
     * @return void
     */
    public function setHardDependencies(array $dependencies): void;

    /**
     * @return string[]
     */
    public function getSoftDependencies(): array;

    /**
     * @return string[]
     */
    public function getHardDependencies(): array;
}
