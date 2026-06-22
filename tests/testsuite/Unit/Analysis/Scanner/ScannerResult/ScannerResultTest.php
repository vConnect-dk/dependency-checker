<?php
declare(strict_types=1);

namespace Vconnect\IntegrityChecker\Tests\Unit\Analysis\Scanner\ScannerResult;

use PHPUnit\Framework\TestCase;
use Vconnect\IntegrityChecker\Analysis\Service\Dependencies\Scanner\ScannerResult\ScannerResult;

class ScannerResultTest extends TestCase
{
    public function testAddAndDedup(): void
    {
        $r = new ScannerResult();
        $r->addHardDependencies(['a', 'b', 'a']);
        $r->addSoftDependencies(['x', 'x']);

        $this->assertSame(['a', 'b'], $r->getHardDependencies());
        $this->assertSame(['x'], $r->getSoftDependencies());
    }
}
