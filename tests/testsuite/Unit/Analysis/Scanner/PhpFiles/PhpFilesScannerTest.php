<?php

declare(strict_types=1);

namespace Vconnect\IntegrityChecker\Tests\Unit\Analysis\Scanner\PhpFiles;

use PHPUnit\Framework\TestCase;
use Vconnect\IntegrityChecker\Analysis\Service\Dependencies\Scanner\PhpFiles;
use Vconnect\IntegrityChecker\Analysis\Service\Dependencies\Scanner\PhpFiles\RegExpFileAnalysis;
use Vconnect\IntegrityChecker\Analysis\Service\Dependencies\Scanner\ScannerResult\ScannerResult;
use Vconnect\IntegrityChecker\Analysis\Service\Dependencies\Scanner\ScannerResult\ScannerResultFactory;
use Vconnect\IntegrityChecker\Application\Filesystem\Data\FileInfo;
use Vconnect\IntegrityChecker\Domain\Config\Di\PluginMap;
use Vconnect\IntegrityChecker\Domain\Package;
use Vconnect\IntegrityChecker\Domain\PackagesRegistry;

class PhpFilesScannerTest extends TestCase
{
    public function testLookupDependenciesClassifiesPluginsAsSoftAndOthersAsHard(): void
    {
        $regExpAnalysis = $this->createStub(RegExpFileAnalysis::class);
        // When scanning a plugin file, it typically references the subject class
        $regExpAnalysis->method('analyzeFile')->willReturn([
            'TestVendor\\Target\\Service',
        ]);

        $pluginMap = $this->createStub(PluginMap::class);
        $pluginMap->method('getPluginMap')->willReturn([
            'TestVendor\\Plugin\\MyPlugin' => 'TestVendor\\Target\\Service',
        ]);

        $registry = $this->createStub(PackagesRegistry::class);
        $registry->method('getPackageNameByNamespace')->willReturnCallback(function (string $ns) {
            $ns = ltrim($ns, '\\');
            if (str_starts_with($ns, 'TestVendor\\Target')) {
                return 'testvendor/target';
            }
            return null;
        });

        $resultFactory = $this->createStub(ScannerResultFactory::class);
        $scannerResult = new ScannerResult();

        $resultFactory->method('create')->willReturn($scannerResult);

        $file = $this->createStub(FileInfo::class);
        $file->method('getExtension')->willReturn('php');

        $package = $this->createStub(Package::class);
        $package->method('getFiles')->willReturn([$file]);
        $package->method('getClassReferenceByPath')->willReturn('TestVendor\\Plugin\\MyPlugin');

        $scanner = new PhpFiles($regExpAnalysis, $pluginMap, $registry, $resultFactory);
        $scanner->lookupDependencies($package);

        // Because the current file is a plugin for the target, the reference becomes a *soft* dependency
        $this->assertContains('testvendor/target', $scannerResult->getSoftDependencies());
        $this->assertNotContains('testvendor/target', $scannerResult->getHardDependencies());
    }
}
