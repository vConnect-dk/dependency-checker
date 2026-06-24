<?php

declare(strict_types=1);

namespace Vconnect\IntegrityChecker\Application\Framework\Events;

use Invoker\InvokerInterface;

readonly class Manager
{
    /**
     * @param array<string, class-string<ObserverInterface>|ObserverInterface> $listeners
     */
    public function __construct(
        private InvokerInterface $invoker,
        private array $listeners = []
    ) {
    }

    public function dispatchEvent(string $eventName, array $eventData): void
    {
        foreach ($this->listeners[$eventName] ?? [] as $observer) {
            $this->invoker->call([$observer, 'execute'], [$eventData]);
        }
    }
}
