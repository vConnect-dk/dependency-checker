<?php

declare(strict_types=1);

use Vconnect\IntegrityChecker\Analysis\Service\Dependencies\Scanner\PhpFiles\RegExpFileAnalysis;
use Vconnect\IntegrityChecker\Application\Observer\PhpFilesAnalysis\CollectDbTablesUsageFromFile;
use Vconnect\IntegrityChecker\Application\Observer\PhpFilesAnalysis\UrlRoutesCollector;

/**
 * Event subscriptions: event name => list of observer class names.
 * Wired into Events\Manager via di.php constructor injection.
 *
 * @return array<string, class-string[]>
 */
return [
    RegExpFileAnalysis::EVENT_NAME => [
        CollectDbTablesUsageFromFile::class,
        UrlRoutesCollector::class,
    ],
];
