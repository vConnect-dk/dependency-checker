<?php

declare(strict_types=1);

namespace Vconnect\IntegrityChecker\Tests\Unit\Domain;

use PHPUnit\Framework\TestCase;
use Vconnect\IntegrityChecker\Domain\Package;
use Vconnect\IntegrityChecker\Domain\Package\LoaderInterface;
use Vconnect\IntegrityChecker\Domain\PackagesRegistry;
use Vconnect\IntegrityChecker\Domain\Project\ComposerProvider;

class PackagesRegistryTest extends TestCase
{
    public function testGetPackageNameByNamespaceReturnsNullForUnknownNamespaceWithoutError(): void
    {
        $loader = $this->createStub(LoaderInterface::class);
        $composerProvider = $this->createStub(ComposerProvider::class);

        $package = $this->createStub(Package::class);
        $package->method('getName')->willReturn('vendor/package-a');
        $package->method('getPackageNamespaces')->willReturn(['Vendor\\PackageA']);

        $loader->method('loadPackages')->willReturn([
            'vendor/package-a' => $package,
        ]);

        $registry = new PackagesRegistry($loader, $composerProvider);

        $this->assertEquals('vendor/package-a', $registry->getPackageNameByNamespace('Vendor\\PackageA\\SomeClass'));
        $this->assertNull($registry->getPackageNameByNamespace('Unknown\\Namespace\\SomeClass'));
    }
}
