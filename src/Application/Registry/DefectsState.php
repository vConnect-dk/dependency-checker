<?php declare(strict_types=1);

namespace Vconnect\IntegrityChecker\Application\Registry;

use Vconnect\IntegrityChecker\Analysis\Data\ResultInterface;

class DefectsState
{
    private bool $hasDefects = false;

    /**
     * @param ResultInterface $result
     * @return void
     */
    public function registerResult(ResultInterface $result)
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
