<?php
declare(strict_types=1);

use Vconnect\IntegrityChecker\Analysis\Service\Dependencies\Scanner\PhpFiles\RegExpFileAnalysis;
use Vconnect\IntegrityChecker\Application\Framework\Events\Manager;
use Vconnect\IntegrityChecker\Application\Observer\PhpFilesAnalysis\CollectDbTablesUsageFromFile;

return [
    Manager::EVENT_LISTENERS => [
        RegExpFileAnalysis::EVENT_NAME => [
            CollectDbTablesUsageFromFile::class,
        ],
    ]
];