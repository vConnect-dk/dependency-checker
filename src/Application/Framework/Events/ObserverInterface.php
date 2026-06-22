<?php

declare(strict_types=1);

namespace Vconnect\IntegrityChecker\Application\Framework\Events;

interface ObserverInterface
{
    public function execute(array $eventData): void;
}
