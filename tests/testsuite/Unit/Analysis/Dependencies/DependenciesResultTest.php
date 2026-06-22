<?php
declare(strict_types=1);

namespace Vconnect\IntegrityChecker\Tests\Unit\Analysis\Dependencies;

use PHPUnit\Framework\TestCase;
use Vconnect\IntegrityChecker\Analysis\Data\Dependencies\Result;
use Vconnect\IntegrityChecker\Analysis\Service\Dependencies\DependencyInterface;

class DependenciesResultTest extends TestCase
{
    public function testHasDefectsDetectsMissingHardAndExpected(): void
    {
        $r = new Result('vendor/pkg', [
            DependencyInterface::TYPE_HARD => ['missed/hard'],
            DependencyInterface::TYPE_SOFT => [],
            DependencyInterface::TYPE_EXCESSIVE => [],
        ], [
            DependencyInterface::TYPE_EXPECTED => ['Missed_Module'],
            DependencyInterface::TYPE_EXCESSIVE => [],
        ]);

        $this->assertTrue($r->hasDefects());
        $this->assertFalse($r->hasNotices());
    }

    public function testHasNoticesDetectsExcessive(): void
    {
        $r = new Result('vendor/pkg', [
            DependencyInterface::TYPE_HARD => [],
            DependencyInterface::TYPE_SOFT => [],
            DependencyInterface::TYPE_EXCESSIVE => ['extra/pkg'],
        ], [
            DependencyInterface::TYPE_EXPECTED => [],
            DependencyInterface::TYPE_EXCESSIVE => ['Extra_Module'],
        ]);

        $this->assertFalse($r->hasDefects());
        $this->assertTrue($r->hasNotices());
    }

    public function testCleanHasNothing(): void
    {
        $r = new Result('vendor/pkg', [
            DependencyInterface::TYPE_HARD => [],
            DependencyInterface::TYPE_SOFT => [],
            DependencyInterface::TYPE_EXCESSIVE => [],
        ], [
            DependencyInterface::TYPE_EXPECTED => [],
            DependencyInterface::TYPE_EXCESSIVE => [],
        ]);

        $this->assertFalse($r->hasDefects());
        $this->assertFalse($r->hasNotices());
    }
}
