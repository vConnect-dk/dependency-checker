<?php
declare(strict_types=1);

namespace Vconnect\IntegrityChecker\Application\Framework\Events;

use Invoker\InvokerInterface;

readonly class Manager
{
    /**
     * @param array<string, class-string<ObserverInterface>[]> $listeners Event name => observer class names
     */
    public function __construct(
        private ?InvokerInterface $invoker = null,
        private array $listeners = []
    ) {
    }

    public function dispatchEvent(string $eventName, array $eventData): void
    {
        $subscriptions = $this->listeners[$eventName] ?? null;
        if ($subscriptions === null) {
            return;
        }

        foreach ($subscriptions as $observer) {
            if ($this->invoker !== null) {
                $this->invoker->call([$observer, 'execute'], ['eventData' => $eventData]);
            } elseif (is_object($observer) && method_exists($observer, 'execute')) {
                // Direct instance (e.g. unit tests without a container invoker)
                $observer->execute(['eventData' => $eventData]);
            }
        }
    }
}
