<?php
declare(strict_types=1);

namespace Vconnect\IntegrityChecker\Analysis\Service\Dependencies\Model;

use Vconnect\IntegrityChecker\Analysis\Service\Dependencies\Scanner\ScannerResult\ScannerResultInterface;

interface DependencyInterface
{
    /**
     * Types of dependencies between modules
     */
    public const TYPE_SOFT = 'soft';
    public const TYPE_HARD = 'hard';

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
