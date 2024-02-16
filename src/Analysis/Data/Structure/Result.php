<?php declare(strict_types=1);

namespace Vconnect\IntegrityChecker\Analysis\Data\Structure;

use Vconnect\IntegrityChecker\Analysis\Data\DefectiveResultInterface;

class Result implements DefectiveResultInterface
{
    private string $packageName;

    private array $missedComponents;

    /**
     * @param string $packageName
     * @param array $missedComponents
     */
    public function __construct(string $packageName, array $missedComponents)
    {
        $this->packageName = $packageName;
        $this->missedComponents = $missedComponents;
    }

    /**
     * @return string
     */
    public function getPackageName(): string
    {
        return $this->packageName;
    }

    /**
     * @return bool
     */
    public function hasDefects(): bool
    {
        return !empty($this->missedComponents);
    }

    /**
     * @return array
     */
    public function getResult(): array
    {
        return $this->missedComponents;
    }
}
