<?php
declare(strict_types=1);

namespace Vconnect\IntegrityChecker\Tests\Unit\Analysis\Dependencies;

use PHPUnit\Framework\TestCase;
use Vconnect\IntegrityChecker\Analysis\Service\Dependencies\Dependency;
use Vconnect\IntegrityChecker\Analysis\Service\Dependencies\DependencyInterface;
use Vconnect\IntegrityChecker\Analysis\Service\Dependencies\Scanner\ScannerResult\ScannerResult;

class DependencyTest extends TestCase
{
    public function testMergeAndFilterSoft(): void
    {
        $dep = new Dependency();

        $sr1 = new ScannerResult();
        $sr1->addHardDependencies(['pkg/a']);
        $sr1->addSoftDependencies(['pkg/b', 'pkg/a']); // the "a" is also hard, should be filtered from soft

        $sr2 = new ScannerResult();
        $sr2->addHardDependencies(['pkg/c']);
        $sr2->addSoftDependencies(['pkg/d']);

        $dep->mergeDependencies($sr1);
        $dep->mergeDependencies($sr2);

        $this->assertSame(['pkg/a', 'pkg/c'], $dep->getHardDependencies());
        $this->assertSame(['pkg/b', 'pkg/d'], $dep->getSoftDependencies());
    }

    public function testSettersDeduplicate(): void
    {
        $dep = new Dependency();
        $dep->setHardDependencies(['x', 'y', 'x']);
        $dep->setSoftDependencies(['s', 's']);

        $this->assertSame(['x', 'y'], $dep->getHardDependencies());
        $this->assertSame(['s'], $dep->getSoftDependencies());
    }
}
