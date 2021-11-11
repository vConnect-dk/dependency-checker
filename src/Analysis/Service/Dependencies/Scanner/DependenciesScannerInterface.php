<?php declare(strict_types=1);

namespace Vconnect\IntegrityChecker\Analysis\Service\Dependencies\Scanner;

use Vconnect\IntegrityChecker\Domain\Package;

interface DependenciesScannerInterface
{
    public function lookupDependencies(Package $package): array;
}
