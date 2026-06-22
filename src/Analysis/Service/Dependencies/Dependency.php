<?php

declare(strict_types=1);

namespace Vconnect\IntegrityChecker\Analysis\Service\Dependencies;

use Vconnect\IntegrityChecker\Analysis\Service\Dependencies\Scanner\ScannerResult\ScannerResultInterface;

class Dependency implements DependencyInterface
{
    private array $dependencies = [
        DependencyInterface::TYPE_SOFT => [],
        DependencyInterface::TYPE_HARD => []
    ];

    public function getHardDependencies(): array
    {
        return $this->dependencies[DependencyInterface::TYPE_HARD];
    }

    public function getSoftDependencies(): array
    {
        return $this->dependencies[DependencyInterface::TYPE_SOFT];
    }

    public function mergeDependencies(ScannerResultInterface $scannerResult): void
    {
        $this->setSoftDependencies($scannerResult->getSoftDependencies());
        $this->setHardDependencies($scannerResult->getHardDependencies());
        $this->filterSoftDependencies();
    }

    private function filterSoftDependencies(): void
    {
        $this->dependencies[DependencyInterface::TYPE_SOFT] = array_filter(
            $this->dependencies[DependencyInterface::TYPE_SOFT],
            fn (string $name): bool => !in_array($name, $this->dependencies[DependencyInterface::TYPE_HARD])
        );
    }

    public function setHardDependencies(array $hardDependency): void
    {
        $this->dependencies[DependencyInterface::TYPE_HARD] =
            array_unique(array_merge($this->getHardDependencies(), $hardDependency));
    }

    public function setSoftDependencies(array $softDependency): void
    {
        $this->dependencies[DependencyInterface::TYPE_SOFT] =
            array_unique(array_merge($this->getSoftDependencies(), $softDependency));
    }
}
