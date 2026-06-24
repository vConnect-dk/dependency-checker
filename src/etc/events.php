<?php

declare(strict_types=1);

use Vconnect\IntegrityChecker\Analysis\Service\Dependencies\Scanner\PhpFiles\RegExpFileAnalysis;
use Vconnect\IntegrityChecker\Application\Observer\PhpFilesAnalysis\CollectDbTablesUsageFromFile;
use Vconnect\IntegrityChecker\Application\Observer\PhpFilesAnalysis\UrlRoutesCollector;

/**
 * Event subscriptions: event name => list of observer class names (or instances).
 *
 * The Manager uses the injected InvokerInterface (the DI container) to resolve
 * class names and invoke execute(). Listeners can be class-strings (resolved
 * via container) or pre-instantiated objects (for tests).
 *
 * @return array<string, list<class-string<\Vconnect\IntegrityChecker\Application\Framework\Events\ObserverInterface>|\Vconnect\IntegrityChecker\Application\Framework\Events\ObserverInterface>>
 */
return [
    RegExpFileAnalysis::EVENT_NAME => [
        CollectDbTablesUsageFromFile::class,
        UrlRoutesCollector::class,
    ],
];
