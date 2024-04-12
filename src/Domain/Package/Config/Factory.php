<?php declare(strict_types=1);

namespace Vconnect\IntegrityChecker\Domain\Package\Config;

use Vconnect\IntegrityChecker\Domain\Package;
use Vconnect\IntegrityChecker\Domain\Package\Config;

class Factory
{
    public function create(Package $package): Config
    {
        return App()->make(Config::class, ['package' => $package]);
    }
}
