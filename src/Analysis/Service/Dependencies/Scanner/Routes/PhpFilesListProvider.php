<?php
declare(strict_types=1);

namespace Vconnect\IntegrityChecker\Analysis\Service\Dependencies\Scanner\Routes;

use Vconnect\IntegrityChecker\Domain\Package;
use Vconnect\IntegrityChecker\Domain\PackagesRegistry;

class PhpFilesListProvider
{
    private ?array $filesList = null;

    public function __construct(
        private readonly PackagesRegistry $packagesRegistry
    ) {
    }

    public function getPhpFiles(): array
    {
        if ($this->filesList === null) {
            $this->filesList = $this->initFilesList();
        }

        return $this->filesList;
    }

    private function initFilesList(): array
    {
        $packages = array_filter(
            $this->packagesRegistry->getAllPackages(),
            fn(Package $package) => $package->getPackageType() === Package::MAGENTO_PACKAGE_TYPE
        );

        $filesList = [];
        foreach ($packages as $package) {
            foreach ($package->getPackageFiles() as $file) {
                $path = $file->getPathname();
                if ($file->getExtension() === 'php' && str_contains($path, '/Controller/')) {
                    $filesList[$package->getName()][] = $path;
                }
            }
        }

        return $filesList;
    }
}