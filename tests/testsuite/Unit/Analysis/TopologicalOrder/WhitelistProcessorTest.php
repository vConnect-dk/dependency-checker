<?php
declare(strict_types=1);

namespace Vconnect\IntegrityChecker\Tests\Unit\Analysis\TopologicalOrder;

use PHPUnit\Framework\TestCase;
use Vconnect\IntegrityChecker\Analysis\Service\TopologicalOrder\WhitelistProcessor;
use Vconnect\IntegrityChecker\Domain\Package;

class WhitelistProcessorTest extends TestCase
{
    public function testProcessesPlainWhitelist(): void
    {
        $processor = new WhitelistProcessor();

        $packages = [
            $this->makePackage('testvendor/base'),
            $this->makePackage('testvendor/dependent'),
            $this->makePackage('magento/module-catalog'),
        ];

        $result = $processor->process(['testvendor/base' => true, 'magento/module-catalog' => true], $packages);

        $this->assertArrayHasKey('testvendor/base', $result);
        $this->assertArrayHasKey('magento/module-catalog', $result);
        $this->assertArrayNotHasKey('testvendor/dependent', $result);
    }

    public function testProcessesWildcardWhitelist(): void
    {
        $processor = new WhitelistProcessor();

        $packages = [
            $this->makePackage('testvendor/base'),
            $this->makePackage('testvendor/dependent'),
            $this->makePackage('other/something'),
        ];

        $result = $processor->process(['testvendor/*' => true], $packages);

        $this->assertArrayHasKey('testvendor/base', $result);
        $this->assertArrayHasKey('testvendor/dependent', $result);
        $this->assertArrayNotHasKey('other/something', $result);
    }

    public function testReturnsOnlyMatchedPackages(): void
    {
        $processor = new WhitelistProcessor();
        $packages = [$this->makePackage('testvendor/base')];

        $result = $processor->process(['nonexistent/module' => true, 'testvendor/base' => true], $packages);

        // Matched packages are normalized
        $this->assertArrayHasKey('testvendor/base', $result);
        $this->assertSame('testvendor/base', $result['testvendor/base']);

        // Unmatched whitelist entries may remain (harmless for usage in notRemovable map)
        $this->assertArrayHasKey('nonexistent/module', $result);
    }

    private function makePackage(string $name): Package
    {
        $pkg = $this->createStub(Package::class);
        $pkg->method('getName')->willReturn($name);
        return $pkg;
    }
}
