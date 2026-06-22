<?php

declare(strict_types=1);

namespace Vconnect\IntegrityChecker\Tests\Unit\Analysis\Scanner\QueueConfig;

use PHPUnit\Framework\TestCase;
use Vconnect\IntegrityChecker\Analysis\Service\Dependencies\Scanner\QueueConfig;
use Vconnect\IntegrityChecker\Analysis\Service\Dependencies\Scanner\QueueConfig\ConfigAnalysis;
use Vconnect\IntegrityChecker\Analysis\Service\Dependencies\Scanner\ScannerResult\ScannerResultFactory;
use Vconnect\IntegrityChecker\Analysis\Service\Dependencies\Scanner\ScannerResult\ScannerResultInterface;
use Vconnect\IntegrityChecker\Domain\Package;
use Vconnect\IntegrityChecker\Domain\Package\Config\Queue;
use Vconnect\IntegrityChecker\Domain\Package\Config;

class QueueConfigScannerTest extends TestCase
{
    public function testLookupDependenciesAddsHardDependenciesFromConfigAnalysis(): void
    {
        $configAnalysis = $this->createStub(ConfigAnalysis::class);
        $configAnalysis->method('analyzeConfigFiles')
            ->willReturn(['magento/module-amqp', 'some/other']);

        $resultFactory = $this->createStub(ScannerResultFactory::class);
        $scannerResult = $this->createMock(ScannerResultInterface::class);

        $scannerResult->expects($this->once())
            ->method('addHardDependencies')
            ->with(['magento/module-amqp', 'some/other']);

        $resultFactory->method('create')->willReturn($scannerResult);

        $queueConfig = $this->createStub(Queue::class);

        $package = $this->createStub(Package::class);
        $package->method('getName')->willReturn('test/pkg');

        $configMock = $this->createStub(Config::class);
        $configMock->method('getQueueConfig')->willReturn($queueConfig);
        $package->method('getConfig')->willReturn($configMock);

        $scanner = new QueueConfig($configAnalysis, $resultFactory);
        $scanner->lookupDependencies($package);
    }
}
