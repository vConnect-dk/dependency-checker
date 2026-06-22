<?php
declare(strict_types=1);

namespace Vconnect\IntegrityChecker\Tests\System;

use PHPUnit\Framework\TestCase;

/**
 * Base for system tests that invoke real CLI binaries and compare stdout/stderr against stored fixtures.
 */
abstract class CliSystemTestCase extends TestCase
{
    protected string $sandboxRoot;
    protected string $projectRoot;
    protected string $fixturesDir;

    protected function setUp(): void
    {
        $this->projectRoot = realpath(__DIR__ . '/../../..');
        $this->sandboxRoot = realpath(__DIR__ . '/../../sandbox');
        $this->fixturesDir = realpath(__DIR__ . '/../../fixtures/system');

        if (!$this->sandboxRoot || !is_dir($this->sandboxRoot)) {
            $this->markTestSkipped('Sandbox directory not found');
        }
        if (!$this->fixturesDir || !is_dir($this->fixturesDir)) {
            $this->markTestSkipped('System fixtures directory not found (tests/fixtures/system)');
        }
    }

    protected function resolveBin(string $name): string
    {
        $bin = realpath($this->projectRoot . '/bin/' . $name);
        if (!$bin || !is_file($bin)) {
            $this->markTestSkipped("bin/{$name} not found");
        }

        return $bin;
    }

    /**
     * Run a CLI command, capturing combined stdout/stderr.
     *
     * @return array{0: string, 1: int} [output, exitCode]
     */
    protected function runCli(string $command): array
    {
        $output = [];
        $exitCode = 0;
        exec($command . ' 2>&1', $output, $exitCode);

        return [implode("\n", $output), $exitCode];
    }

    protected function loadFixture(string $basename): string
    {
        $path = $this->fixturesDir . '/' . $basename;
        $this->assertFileExists($path, "Expected output fixture missing: {$basename}");

        return (string) file_get_contents($path);
    }

    protected function loadExpectedExitCode(string $basename): int
    {
        $path = $this->fixturesDir . '/' . $basename;
        $this->assertFileExists($path, "Expected exit-code fixture missing: {$basename}");

        return (int) trim((string) file_get_contents($path));
    }

    /**
     * Compare CLI output (+ exit code) against committed fixtures under tests/fixtures/system/.
     */
    protected function assertCliMatchesFixture(
        string $actualOutput,
        int $actualExitCode,
        string $outputFixtureBasename,
        string $exitFixtureBasename
    ): void {
        $expectedOutput = $this->normalizeCliOutput($this->loadFixture($outputFixtureBasename));
        $actualOutput = $this->normalizeCliOutput($actualOutput);
        $expectedExit = $this->loadExpectedExitCode($exitFixtureBasename);

        $this->assertSame(
            $expectedExit,
            $actualExitCode,
            sprintf(
                "Exit code mismatch for fixture '%s'.\nExpected: %d\nActual: %d\n--- actual output ---\n%s",
                $exitFixtureBasename,
                $expectedExit,
                $actualExitCode,
                $actualOutput
            )
        );

        $this->assertSame(
            $expectedOutput,
            $actualOutput,
            sprintf(
                "CLI output does not match fixture '%s'.\nUpdate tests/fixtures/system/%s if the change is intentional.\n--- expected ---\n%s\n--- actual ---\n%s",
                $outputFixtureBasename,
                $outputFixtureBasename,
                $expectedOutput,
                $actualOutput
            )
        );
    }

    protected function normalizeCliOutput(string $output): string
    {
        // Normalize newlines; rtrim each line (CLI may print trailing spaces on "Layer N " headers)
        // and strip a final trailing newline so file fixtures and exec()/implode() compare cleanly.
        $output = str_replace(["\r\n", "\r"], "\n", $output);
        $lines = explode("\n", $output);
        $lines = array_map(static fn(string $line): string => rtrim($line), $lines);

        return rtrim(implode("\n", $lines), "\n");
    }
}
