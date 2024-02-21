<?php
declare(strict_types=1);

namespace Vconnect\IntegrityChecker\Application\Framework\Events;

use DI\NotFoundException;

class Manager
{
    public const EVENT_LISTENERS = 'event_listeners';

    public function dispatchEvent(string $eventName, array $eventData): void
    {
        $subscriptions = $this->getEventSubscriptions($eventName);
        if ($subscriptions === null) {
            return;
        }

        foreach ($subscriptions as $observer) {
            App()->call([$observer, 'execute'], ['eventData' => $eventData]);
        }
    }

    private function getEventSubscriptions(string $eventName): ?array
    {
        try {
            $subscriptions = App()->get(self::EVENT_LISTENERS);
            return $subscriptions[$eventName] ?? null;
        } catch (NotFoundException) {
            return null;
        }
    }
}