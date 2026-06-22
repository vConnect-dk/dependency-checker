<?php
declare(strict_types=1);

namespace Vconnect\IntegrityChecker\Tests\Integration;

use Vconnect\IntegrityChecker\Analysis\Service\Dependencies;
use Vconnect\IntegrityChecker\Analysis\Service\Dependencies\DependencyInterface;
use Vconnect\IntegrityChecker\Domain\PackagesRegistry;

/**
 * Integration test: run Dependencies analyzer over sandbox fixture modules.
 * This exercises all scanners indirectly.
 */
class DependenciesAnalyzerIntegrationTest extends SandboxIntegrationTestCase
{
    private const FIXTURE_PACKAGES = [
        self::FIXTURE_BASE,
        self::FIXTURE_DEPENDENT,
        self::FIXTURE_CLEAN,
    ];

    public function testDependentModuleReportsMissingBaseDependencies(): void
    {
        /** @var PackagesRegistry $registry */
        $registry = $this->container->get(PackagesRegistry::class);

        $fixtures = array_filter(
            $registry->getAllPackages(),
            fn($p) => in_array($p->getName(), self::FIXTURE_PACKAGES, true)
        );

        /** @var Dependencies $analyzer */
        $analyzer = $this->container->get(Dependencies::class);

        $results = [];
        foreach ($analyzer->analyse($fixtures) as $result) {
            $results[$result->getPackageName()] = $result;
        }

        $this->assertArrayHasKey(self::FIXTURE_DEPENDENT, $results);
        $depResult = $results[self::FIXTURE_DEPENDENT];
        $this->assertTrue($depResult->hasDefects(), 'Dependent should report missing dependencies');

        $data = $depResult->getResult();
        $missedComposerHard = array_values($data['composer'][DependencyInterface::TYPE_HARD] ?? []);
        $missedModuleExpected = array_values($data['module'][DependencyInterface::TYPE_EXPECTED] ?? []);

        $this->assertContains(
            self::FIXTURE_BASE,
            $missedComposerHard,
            'Expected missed hard composer dependency: testvendor/base. Got: ' . json_encode($missedComposerHard)
        );

        $this->assertContains(
            'TestVendor_Base',
            $missedModuleExpected,
            'Expected missed module.xml dependency: TestVendor_Base. Got: ' . json_encode($missedModuleExpected)
        );
    }

    /**
     * Clean fixture declares testvendor/base in composer.json and TestVendor_Base in module.xml sequence.
     * Analysis must not report Base as a missing dependency for this package.
     */
    public function testCleanModuleDoesNotReportMissingBaseDependency(): void
    {
        /** @var PackagesRegistry $registry */
        $registry = $this->container->get(PackagesRegistry::class);

        $clean = array_filter(
            $registry->getAllPackages(),
            fn($p) => $p->getName() === self::FIXTURE_CLEAN
        );

        /** @var Dependencies $analyzer */
        $analyzer = $this->container->get(Dependencies::class);

        $found = false;
        foreach ($analyzer->analyse($clean) as $result) {
            $found = true;
            $data = $result->getResult();

            $missedHard = array_values($data['composer'][DependencyInterface::TYPE_HARD] ?? []);
            $this->assertNotContains(
                self::FIXTURE_BASE,
                $missedHard,
                'Clean should not miss testvendor/base in composer hard defects. Got: ' . json_encode($missedHard)
            );

            $missedModule = array_values($data['module'][DependencyInterface::TYPE_EXPECTED] ?? []);
            $this->assertNotContains(
                'TestVendor_Base',
                $missedModule,
                'Clean should not miss TestVendor_Base in module.xml expected defects. Got: ' . json_encode($missedModule)
            );
        }

        $this->assertTrue($found, 'Clean module should have been analyzed');
    }
}
