<?php
declare(strict_types=1);

namespace Vconnect\IntegrityChecker\Analysis\Service\Dependencies\Model;

use Vconnect\IntegrityChecker\Analysis\Service\Dependencies\Scanner\ScannerResult\ScannerResultInterface;

class Dependency implements DependencyInterface
{
    private array $dependencies = [
        DependencyInterface::TYPE_SOFT => [],
        DependencyInterface::TYPE_HARD => []
    ];

    /**
     * @return array
     */
    public function getHardDependency(): array
    {
        return $this->dependencies[DependencyInterface::TYPE_HARD];
    }

    /**
     * @return array
     */
    public function getSoftDependency(): array
    {
        return $this->dependencies[DependencyInterface::TYPE_SOFT];
    }

    /**
     * @param ScannerResultInterface $scannerResult
     * @return void
     */
    public function mergeDependencies(ScannerResultInterface $scannerResult): void
    {
        $this->dependencies[DependencyInterface::TYPE_SOFT] = $scannerResult->getSoftDependencies();
        $this->dependencies[DependencyInterface::TYPE_HARD] = $scannerResult->getHardDependencies();
        $this->filterSoftDependencies();
    }

    private function filterSoftDependencies(): void
    {
        $this->dependencies[DependencyInterface::TYPE_SOFT] = array_filter(
            $this->dependencies[DependencyInterface::TYPE_SOFT],
            fn(string $name) => !in_array($name, $this->dependencies[DependencyInterface::TYPE_HARD])
        );
    }
}
