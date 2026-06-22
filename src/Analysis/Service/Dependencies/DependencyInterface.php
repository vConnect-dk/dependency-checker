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
    public const TYPE_EXCESSIVE = 'excessive';
    public const TYPE_EXPECTED = 'expected';


    public function setHardDependencies(array $hardDependency): void;

    public function setSoftDependencies(array $softDependency): void;

    public function getHardDependencies(): array;

    public function getSoftDependencies(): array;

    public function mergeDependencies(ScannerResultInterface $scannerResult): void;
}
