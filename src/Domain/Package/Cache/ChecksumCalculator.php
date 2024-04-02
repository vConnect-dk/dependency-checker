<?php
declare(strict_types=1);

namespace Vconnect\IntegrityChecker\Domain\Package\Cache;

use Vconnect\IntegrityChecker\Application\Filesystem\DirectoryRegistry;

class ChecksumCalculator
{
    public function getCheckSum(): string
    {
        return hash_file('md5', DirectoryRegistry::getRoot() . 'vendor/composer/installed.php');
    }
}
