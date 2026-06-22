<?php
declare(strict_types=1);

namespace Vconnect\IntegrityChecker\Tests\Unit\Analysis\TopologicalOrder;

use PHPUnit\Framework\TestCase;
use Vconnect\IntegrityChecker\Analysis\Service\TopologicalOrder\NotMagentoExtensionsProcessor;
use Vconnect\IntegrityChecker\Domain\Package;

class NotMagentoExtensionsProcessorTest extends TestCase
{
    public function testIdentifiesNonMagentoPackages(): void
    {
        $processor = new NotMagentoExtensionsProcessor();

        $packages = [
            $this->makePackage('magento/module-catalog', 'magento2-module'),
            $this->makePackage('testvendor/base', 'magento2-module'),
            $this->makePackage('some/library', 'library'),           // not magento
            $this->makePackage('external/component', 'magento2-component'),
            $this->makePackage('random/package', 'metapackage'),
        ];

        $result = $processor->process($packages);

        $this->assertArrayNotHasKey('magento/module-catalog', $result);
        $this->assertArrayNotHasKey('testvendor/base', $result);

        // Only PACKAGE and LIBRARY are kept as removable candidates.
        // COMPONENT and other types are treated as non-magento (protected from removal).
        $this->assertArrayHasKey('external/component', $result);
        $this->assertArrayHasKey('some/library', $result);
        $this->assertArrayHasKey('random/package', $result);
    }

    private function makePackage(string $name, string $type): Package
    {
        $pkg = $this->createStub(Package::class);
        $pkg->method('getName')->willReturn($name);
        $pkg->method('getPackageType')->willReturn($type);
        return $pkg;
    }
}
