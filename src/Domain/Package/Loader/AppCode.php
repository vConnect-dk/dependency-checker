<?php

declare(strict_types=1);

namespace Vconnect\IntegrityChecker\Domain\Package\Loader;

use Vconnect\IntegrityChecker\Domain\Package\LoaderInterface;
use Vconnect\IntegrityChecker\Domain\Scanner\FileSystemPackagesProvider;

class AppCode implements LoaderInterface
{
    private const MAGENTO_LOCAL = 'app/code';

    public function __construct(
        private readonly FileSystemPackagesProvider $fsScanner
    ) {
    }

    public function loadPackages(): array
    {
        $packages = [];
        foreach ($this->fsScanner->getPackagesRecursively(
            paths: [self::MAGENTO_LOCAL],
            fileMask: '/registration.php/'
        ) as $appCodePackage) {
            $packages[$appCodePackage->getName()] = $appCodePackage;
        }

        return $packages;
    }
}
