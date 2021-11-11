<?php declare(strict_types=1);

namespace Vconnect\IntegrityChecker\Application;

use Vconnect\IntegrityChecker\Analysis\Data\ResultInterface;

interface ConsoleInterface
{
    public function validateParameters(): bool;

    public function printHelp(): void;

    public function printOutput(ResultInterface $result): void;

    public function getStatusCode(): int;
}
