<?php

declare(strict_types=1);

namespace Vconnect\IntegrityChecker\Analysis\Service\Dependencies\Scanner\DbDDL;

use SplObjectStorage;
use Vconnect\IntegrityChecker\Domain\Package;

class PackageTablesUsageRegistry
{
    private SplObjectStorage $usagesPerPackage;

    public function __construct()
    {
        $this->usagesPerPackage = new SplObjectStorage();
    }

    public function add(Package $package, array $tables): void
    {
        $usages = $this->usagesPerPackage[$package] ?? [];
        foreach ($tables as $table) {
            if (!isset($usages[$table])) {
                $usages[$table] = true;
            }
        }
        $this->usagesPerPackage[$package] = $usages;
    }

    public function get(Package $package): array
    {
        $usages = $this->usagesPerPackage[$package] ?? [];
        return array_keys($usages);
    }
}
