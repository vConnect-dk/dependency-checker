<?php
declare(strict_types=1);

namespace Vconnect\IntegrityChecker\Tests\System;

/**
 * System tests: invoke bin/dependencies against sandbox TestVendor fixtures
 * and assert exact CLI output (+ exit code) against committed fixtures.
 */
class DependenciesCliSystemTest extends CliSystemTestCase
{
    private string $bin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->bin = $this->resolveBin('dependencies');
    }

    public function testCliOutputMatchesFixtureForTestVendorModules(): void
    {
        $cmd = sprintf(
            'php %s %s app/code/TestVendor',
            escapeshellarg($this->bin),
            escapeshellarg($this->sandboxRoot)
        );

        [$output, $exitCode] = $this->runCli($cmd);

        $this->assertCliMatchesFixture(
            $output,
            $exitCode,
            'dependencies_testvendor.txt',
            'dependencies_testvendor.exit'
        );
    }
}
