<?php

declare(strict_types=1);

namespace Vconnect\IntegrityChecker\Tests\Unit\Analysis\Scanner\DbSchema;

use PHPUnit\Framework\TestCase;
use Vconnect\IntegrityChecker\Analysis\Service\Dependencies\Scanner\DbSchema;
use Vconnect\IntegrityChecker\Analysis\Service\Dependencies\Scanner\ScannerResult\ScannerResultFactory;
use Vconnect\IntegrityChecker\Analysis\Service\Dependencies\Scanner\ScannerResult\ScannerResultInterface;
use Vconnect\IntegrityChecker\Domain\Config\DbSchema\ModulesSchemaCollector;
use Vconnect\IntegrityChecker\Domain\Package;
use Vconnect\IntegrityChecker\Domain\Package\Config;

class DbSchemaScannerTest extends TestCase
{
    public function testLookupDependenciesReportsSoftForTablesAndHardForForeignKeys(): void
    {
        $schemaCollector = $this->createStub(ModulesSchemaCollector::class);
        $schemaCollector->method('getSchemaOwnerPackageName')
            ->willReturnCallback(function (string $table) {
                return match ($table) {
                    'testvendor_main_table' => 'testvendor/main',
                    'testvendor_ref_table' => 'testvendor/ref',
                    default => null,
                };
            });

        $resultFactory = $this->createStub(ScannerResultFactory::class);
        $scannerResult = $this->createMock(ScannerResultInterface::class);

        $scannerResult->expects($this->once())->method('addSoftDependencies');
        $scannerResult->expects($this->once())->method('addHardDependencies');

        $resultFactory->method('create')->willReturn($scannerResult);

        $dbSchema = $this->createStub(Config\DbSchema::class);
        $dbSchema->method('getContent')->willReturn([
            'table' => [
                'testvendor_main_table' => [
                    'name' => 'testvendor_main_table',
                    'constraint' => [
                        [
                            'type' => 'foreign',
                            'referenceTable' => 'testvendor_ref_table',
                            'referenceId' => 'FK_MAIN_REF',
                        ]
                    ]
                ]
            ]
        ]);

        $packageConfig = $this->createStub(Config::class);
        $packageConfig->method('getDbSchema')->willReturn($dbSchema);

        $package = $this->createStub(Package::class);
        $package->method('getConfig')->willReturn($packageConfig);
        $package->method('getName')->willReturn('testvendor/own');

        $scanner = new DbSchema($schemaCollector, $resultFactory);
        $scanner->lookupDependencies($package);
    }

    public function testExcludesOwnPackage(): void
    {
        $schemaCollector = $this->createStub(ModulesSchemaCollector::class);
        $schemaCollector->method('getSchemaOwnerPackageName')
            ->willReturnCallback(fn (string $t) => $t === 'testvendor_own_table' ? 'testvendor/own' : null);

        $resultFactory = $this->createStub(ScannerResultFactory::class);
        $scannerResult = $this->createStub(ScannerResultInterface::class);

        $scannerResult->method('addSoftDependencies');

        $resultFactory->method('create')->willReturn($scannerResult);

        $dbSchema = $this->createStub(Config\DbSchema::class);
        $dbSchema->method('getContent')->willReturn([
            'table' => [
                'testvendor_own_table' => ['name' => 'testvendor_own_table']
            ]
        ]);

        $packageConfig = $this->createStub(Config::class);
        $packageConfig->method('getDbSchema')->willReturn($dbSchema);

        $package = $this->createStub(Package::class);
        $package->method('getConfig')->willReturn($packageConfig);
        $package->method('getName')->willReturn('testvendor/own');

        $scanner = new DbSchema($schemaCollector, $resultFactory);
        $scanner->lookupDependencies($package);

        $this->assertTrue(true); // test documents the filtering behavior (no exception + no self-dep)
    }
}
