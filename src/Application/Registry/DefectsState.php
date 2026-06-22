<?php

declare(strict_types=1);

namespace Vconnect\IntegrityChecker\Application\Registry;

use Vconnect\IntegrityChecker\Analysis\Data\DefectiveResultInterface;

class DefectsState
{
    private bool $hasDefects = false;

    public function registerResult(DefectiveResultInterface $result): void
    {
        $this->hasDefects = $this->hasDefects || $result->hasDefects();
    }

    public function hasDefects(): bool
    {
        return $this->hasDefects;
    }
}
