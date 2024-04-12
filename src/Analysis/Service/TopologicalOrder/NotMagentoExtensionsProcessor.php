<?php
declare(strict_types=1);

namespace Vconnect\IntegrityChecker\Analysis\Service\TopologicalOrder;

use Vconnect\IntegrityChecker\Domain\Package;

class NotMagentoExtensionsProcessor
{
    public function process(iterable $packages): array
    {
        $notMagento = [];

        foreach ($packages as $package) {
            if (!in_array($package->getPackageType(), [
                Package::MAGENTO_PACKAGE_TYPE,
                Package::MAGENTO_LIBRARY_TYPE
            ])) {
                $notMagento[$package->getName()] = $package->getName();
            }
        }
        return $notMagento;
    }
}
