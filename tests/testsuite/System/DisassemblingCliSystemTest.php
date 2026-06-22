<?php
declare(strict_types=1);

namespace Vconnect\IntegrityChecker\Tests\System;

/**
 * System tests for bin/disassembling: exact CLI output (+ exit code) vs tests/fixtures/system/.
 */
class DisassemblingCliSystemTest extends CliSystemTestCase
{
    private string $bin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->bin = $this->resolveBin('disassembling');
    }

    public function testCliWithoutExplainMatchesDefaultFixture(): void
    {
        $cmd = sprintf(
            'php %s %s -nc',
            escapeshellarg($this->bin),
            escapeshellarg($this->sandboxRoot)
        );

        [$output, $exitCode] = $this->runCli($cmd);

        $this->assertCliMatchesFixture(
            $output,
            $exitCode,
            'disassembling_default.txt',
            'disassembling_default.exit'
        );
    }

    public function testCliWithExplainForKnownModuleMatchesFixture(): void
    {
        $cmd = sprintf(
            'php %s %s -e testvendor/dependent -nc',
            escapeshellarg($this->bin),
            escapeshellarg($this->sandboxRoot)
        );

        [$output, $exitCode] = $this->runCli($cmd);

        $this->assertCliMatchesFixture(
            $output,
            $exitCode,
            'disassembling_explain_dependent.txt',
            'disassembling_explain_dependent.exit'
        );
    }

    public function testCliWithExplainForNonExistentModuleMatchesFixture(): void
    {
        $cmd = sprintf(
            'php %s %s -e nonexistent/vendor/module -nc',
            escapeshellarg($this->bin),
            escapeshellarg($this->sandboxRoot)
        );

        [$output, $exitCode] = $this->runCli($cmd);

        $this->assertCliMatchesFixture(
            $output,
            $exitCode,
            'disassembling_explain_missing.txt',
            'disassembling_explain_missing.exit'
        );
    }

    public function testCliWithWhitelistExplainMatchesFixture(): void
    {
        $cmd = sprintf(
            'php %s %s -w testvendor/base -e testvendor/base -nc',
            escapeshellarg($this->bin),
            escapeshellarg($this->sandboxRoot)
        );

        [$output, $exitCode] = $this->runCli($cmd);

        $this->assertCliMatchesFixture(
            $output,
            $exitCode,
            'disassembling_explain_whitelisted_base.txt',
            'disassembling_explain_whitelisted_base.exit'
        );
    }
}
