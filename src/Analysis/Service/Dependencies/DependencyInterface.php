<?php
declare(strict_types=1);

namespace Vconnect\IntegrityChecker\Analysis\Service\Dependencies;

use Vconnect\IntegrityChecker\Analysis\Service\Dependencies\Scanner\ScannerResult\ScannerResultInterface;

interface DependencyInterface
{
    /**
     * Types of dependencies between modules
     */
    public const TYPE_SOFT = 'soft';
    public const TYPE_HARD = 'hard';

    /**
     * @param array $hardDependency
     * @return void
     */
    public function setHardDependency(array $hardDependency): void;

    /**
     * @param array $softDependency
     * @return void
     */
    public function setSoftDependency(array $softDependency): void;

    /**
     * @return array
     */
    public function getHardDependency(): array;

    /**
     * @return array
     */
    public function getSoftDependency(): array;

    /**
     * @param ScannerResultInterface $scannerResult
     * @return void
     */
    public function mergeDependencies(ScannerResultInterface $scannerResult): void;
}
