<?php

declare(strict_types=1);

namespace Vconnect\IntegrityChecker\Analysis\Data\Structure;

use Vconnect\IntegrityChecker\Analysis\Data\DefectiveResultInterface;

class Result implements DefectiveResultInterface
{
    public function __construct(private readonly string $packageName, private readonly array $missedComponents)
    {
    }

    public function getPackageName(): string
    {
        return $this->packageName;
    }

    public function hasDefects(): bool
    {
        return $this->missedComponents !== [];
    }

    public function getResult(): array
    {
        return $this->missedComponents;
    }
}
