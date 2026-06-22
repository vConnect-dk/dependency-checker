<?php

declare(strict_types=1);

namespace Vconnect\IntegrityChecker\Analysis\Data;

/**
 * DTO for storing analyse results.
 */
interface DefectiveResultInterface extends ResultInterface
{
    public function hasDefects(): bool;

    public function getPackageName(): string;
}
