<?php
declare(strict_types=1);

namespace Vconnect\IntegrityChecker\Domain\Package;

use Vconnect\IntegrityChecker\Domain\Package;

interface LoaderInterface
{
    /**
     * @return Package[]
     */
    public function loadPackages(): array;
}
