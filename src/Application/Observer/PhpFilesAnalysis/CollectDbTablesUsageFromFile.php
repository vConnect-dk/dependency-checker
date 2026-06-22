<?php

declare(strict_types=1);

namespace Vconnect\IntegrityChecker\Application\Observer\PhpFilesAnalysis;

use Vconnect\IntegrityChecker\Analysis\Service\Dependencies\Scanner\DbDDL\PackageTablesUsageRegistry;
use Vconnect\IntegrityChecker\Analysis\Service\Dependencies\Scanner\PhpFiles\DDLUsageRegexpAnalyzer;
use Vconnect\IntegrityChecker\Application\Framework\Events\ObserverInterface;
use Vconnect\IntegrityChecker\Domain\Package;

class CollectDbTablesUsageFromFile implements ObserverInterface
{
    public function __construct(
        private readonly DDLUsageRegexpAnalyzer     $ddlUsageRegexpAnalyzer,
        private readonly PackageTablesUsageRegistry $packageTablesUsageRegistry
    ) {
    }

    public function execute(array $eventData): void
    {
        $content = $eventData['fileContent'];
        /** @var Package $package */
        $package = $eventData['package'];

        $tables = $this->ddlUsageRegexpAnalyzer->getTablesUsed($content);
        $this->packageTablesUsageRegistry->add($package, $tables);
    }
}
