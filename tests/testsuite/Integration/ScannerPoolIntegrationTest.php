<?php

declare(strict_types=1);

namespace Vconnect\IntegrityChecker\Tests\Integration;

use Vconnect\IntegrityChecker\Analysis\Service\Dependencies\Scanner\DbDDL;
use Vconnect\IntegrityChecker\Analysis\Service\Dependencies\Scanner\DbSchema;
use Vconnect\IntegrityChecker\Analysis\Service\Dependencies\Scanner\DependenciesScannerInterface;
use Vconnect\IntegrityChecker\Analysis\Service\Dependencies\Scanner\GraphQlSchema;
use Vconnect\IntegrityChecker\Analysis\Service\Dependencies\Scanner\Layouts;
use Vconnect\IntegrityChecker\Analysis\Service\Dependencies\Scanner\PhpFiles;
use Vconnect\IntegrityChecker\Analysis\Service\Dependencies\Scanner\QueueConfig;
use Vconnect\IntegrityChecker\Analysis\Service\Dependencies\Scanner\Routes;
use Vconnect\IntegrityChecker\Analysis\Service\Dependencies\Scanner\ScannerResult\ScannerResultInterface;
use Vconnect\IntegrityChecker\Analysis\Service\Dependencies\Scanner\XmlConfigFiles;
use Vconnect\IntegrityChecker\Analysis\Service\Dependencies\ScannerPool;

/**
 * Integration test: ScannerPool is wired via DI with every scanner implementation,
 * and each scanner can execute against a real sandbox package without errors.
 */
class ScannerPoolIntegrationTest extends SandboxIntegrationTestCase
{
    /** @var list<class-string<DependenciesScannerInterface>> */
    private const EXPECTED_SCANNERS = [
        PhpFiles::class,
        XmlConfigFiles::class,
        DbSchema::class,
        QueueConfig::class,
        GraphQlSchema::class,
        DbDDL::class,
        Routes::class,
        Layouts::class,
    ];

    public function testPoolContainsAllExpectedScannerTypesFromDi(): void
    {
        /** @var ScannerPool $pool */
        $pool = $this->container->get(ScannerPool::class);

        $resolvedClasses = [];
        foreach ($pool as $scanner) {
            $this->assertInstanceOf(DependenciesScannerInterface::class, $scanner);
            $resolvedClasses[] = $scanner::class;
        }

        $this->assertSame(
            self::EXPECTED_SCANNERS,
            $resolvedClasses,
            'ScannerPool must contain exactly the scanners registered in di.php, in order'
        );
    }

    public function testEachScannerRunsAgainstSandboxDependentPackage(): void
    {
        $package = $this->getFixturePackage(self::FIXTURE_DEPENDENT);

        /** @var ScannerPool $pool */
        $pool = $this->container->get(ScannerPool::class);

        $ran = [];
        foreach ($pool as $scanner) {
            $result = $scanner->lookupDependencies($package);
            $this->assertInstanceOf(
                ScannerResultInterface::class,
                $result,
                $scanner::class . ' must return ScannerResultInterface'
            );
            $ran[] = $scanner::class;
        }

        $this->assertSame(self::EXPECTED_SCANNERS, $ran);
    }
}
