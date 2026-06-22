<?php

declare(strict_types=1);

namespace Vconnect\IntegrityChecker\Tests\Unit\Analysis\Scanner\XmlConfigFiles;

use PHPUnit\Framework\TestCase;
use Vconnect\IntegrityChecker\Analysis\Service\Dependencies\Scanner\ScannerResult\ScannerResultFactory;
use Vconnect\IntegrityChecker\Analysis\Service\Dependencies\Scanner\ScannerResult\ScannerResultInterface;
use Vconnect\IntegrityChecker\Analysis\Service\Dependencies\Scanner\XmlConfigFiles;
use Vconnect\IntegrityChecker\Analysis\Service\Dependencies\Scanner\XmlConfigFiles\XmlFileAnalysis;
use Vconnect\IntegrityChecker\Domain\Package;

class XmlConfigFilesScannerTest extends TestCase
{
    public function testLookupDependenciesMergesSoftAndHard(): void
    {
        $xmlAnalysis = $this->createStub(XmlFileAnalysis::class);
        $xmlAnalysis->method('analyze')
            ->willReturnOnConsecutiveCalls(
                ['soft/pkg'],           // soft call
                ['hard/pkg', 'another'] // hard call
            );

        $resultFactory = $this->createStub(ScannerResultFactory::class);
        $scannerResult = $this->createMock(ScannerResultInterface::class);

        $scannerResult->expects($this->once())->method('addSoftDependencies');
        $scannerResult->expects($this->once())->method('addHardDependencies');

        $resultFactory->method('create')->willReturn($scannerResult);

        $package = $this->createStub(Package::class);

        $scanner = new XmlConfigFiles($xmlAnalysis, $resultFactory);
        $scanner->lookupDependencies($package);
    }
}
