<?php declare(strict_types=1);

namespace Vconnect\IntegrityChecker\Application\Registry;

use Vconnect\IntegrityChecker\Analysis\Data\DefectiveResultInterface;

class DefectsState
{
    private bool $hasDefects = false;

    /**
     * @param DefectiveResultInterface $result
     *
     * @return void
     */
    public function registerResult(DefectiveResultInterface $result): void
    {
        $this->hasDefects = $this->hasDefects || $result->hasDefects();
    }

    /**
     * @return bool
     */
    public function hasDefects(): bool
    {
        return $this->hasDefects;
    }
}
