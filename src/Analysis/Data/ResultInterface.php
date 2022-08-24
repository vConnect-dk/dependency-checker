<?php declare(strict_types=1);

namespace Vconnect\IntegrityChecker\Analysis\Data;

/**
 * DTO for storing analyse results.
 */
interface ResultInterface
{
    /**
     * @return bool
     */
    public function hasDefects(): bool;

    /**
     * @return string
     */
    public function getPackageName(): string;

    /**
     * @return array
     */
    public function getDefects(): array;
}
