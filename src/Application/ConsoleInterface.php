<?php declare(strict_types=1);

namespace Vconnect\IntegrityChecker\Application;

use Vconnect\IntegrityChecker\Analysis\Data\ResultInterface;

interface ConsoleInterface
{
    /**
     * @return bool
     */
    public function validateParameters(): bool;

    /**
     * @return void
     */
    public function printHelp(): void;

    /**
     * @param ResultInterface $result
     * @return void
     */
    public function printOutput(mixed $result): void;

    /**
     * @return int
     */
    public function getStatusCode(): int;
}
