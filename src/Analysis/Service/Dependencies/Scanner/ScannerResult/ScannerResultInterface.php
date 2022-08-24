<?php
declare(strict_types=1);

namespace Vconnect\IntegrityChecker\Analysis\Service\Dependencies\Scanner\ScannerResult;

interface ScannerResultInterface
{
    /**
     * @param array $dependencies
     * @return void
     */
    public function setSoftDependencies(array $dependencies): void;

    /**
     * @param array $dependencies
     * @return void
     */
    public function setHardDependencies(array $dependencies): void;

    /**
     * @return array
     */
    public function getSoftDependencies(): array;

    /**
     * @return array
     */
    public function getHardDependencies(): array;

}
