<?php

declare(strict_types=1);

namespace Vconnect\IntegrityChecker\Application;

use Vconnect\IntegrityChecker\Analysis\Data\DefectiveResultInterface;

interface ConsoleInterface
{
    public function validateParameters(): bool;

    public function printHelp(): void;

    public function printOutput(DefectiveResultInterface $result): void;

    public function getStatusCode(): int;
}
