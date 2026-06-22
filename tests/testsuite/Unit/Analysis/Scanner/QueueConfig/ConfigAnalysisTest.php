<?php

declare(strict_types=1);

namespace Vconnect\IntegrityChecker\Tests\Unit\Analysis\Scanner\QueueConfig;

use DOMDocument;
use PHPUnit\Framework\TestCase;
use Vconnect\IntegrityChecker\Analysis\Service\Dependencies\Scanner\QueueConfig\ConfigAnalysis;
use Vconnect\IntegrityChecker\Domain\Package\Config\Queue;
use Vconnect\IntegrityChecker\Domain\PackagesRegistry;
use Vconnect\IntegrityChecker\Application\Filesystem\Data\FileInfo;

class ConfigAnalysisTest extends TestCase
{
    private PackagesRegistry $registry;
    private ConfigAnalysis $analyzer;

    protected function setUp(): void
    {
        $this->registry = $this->createStub(PackagesRegistry::class);
        $this->analyzer = new ConfigAnalysis($this->registry);
    }

    public function testDetectsDependenciesFromCommunicationRequestResponseAndHandlers(): void
    {
        $dom = new DOMDocument();
        $dom->loadXML(<<<'XML'
<config>
    <topic name="order.created" request="TestVendor\Order\Api\Data\OrderInterface" response="TestVendor\Order\Api\Data\OrderResponse">
        <handler name="sendEmail" type="TestVendor\Email\Model\Sender" method="execute"/>
        <handler name="disabledOne" type="TestVendor\Disabled\Handler" disabled="true"/>
    </topic>
</config>
XML);

        $queue = new Queue($this->makeFileInfo($dom), null, null, null);

        $this->registry->method('getRealPackageNamespace')->willReturnCallback(function (string $type) {
            if (str_contains($type, 'TestVendor\\Order')) {
                return 'TestVendor\\Order';
            }
            if (str_contains($type, 'TestVendor\\Email')) {
                return 'TestVendor\\Email';
            }
            return null;
        });
        $this->registry->method('getPackageNameByNamespace')->willReturnMap([
            ['TestVendor\\Order', 'testvendor/order'],
            ['TestVendor\\Email', 'testvendor/email'],
        ]);

        $deps = $this->analyzer->analyzeConfigFiles($queue, 'testvendor/own');

        $this->assertContains('testvendor/order', $deps);
        $this->assertContains('testvendor/email', $deps);
        $this->assertNotContains('testvendor/disabled', $deps);
    }

    public function testDetectsMysqlMqAndAmqpFromConsumers(): void
    {
        $dom = new DOMDocument();
        $dom->loadXML(<<<'XML'
<config>
    <consumer name="amqp.consumer" queue="amqp.q" handler="Some\Handler" connection="amqp"/>
    <consumer name="db.consumer" queue="db.q" consumerInstance="Some\Instance" connection="db"/>
</config>
XML);

        $queue = new Queue(null, $this->makeFileInfo($dom), null, null);

        $this->registry->method('getRealPackageNamespace')->willReturn(null);
        $this->registry->method('getPackageNameByNamespace')->willReturn(null);

        $deps = $this->analyzer->analyzeConfigFiles($queue, 'testvendor/own');

        $this->assertContains('magento/module-amqp', $deps);
        $this->assertContains('magento/module-mysql-mq', $deps);
    }

    public function testDetectsAmqpAndDbFromPublishers(): void
    {
        $dom = new DOMDocument();
        $dom->loadXML(<<<'XML'
<config>
    <publisher topic="some.topic">
        <connection name="amqp" exchange="ex" disabled="false"/>
        <connection name="db" exchange="ex" disabled="true"/>
    </publisher>
</config>
XML);

        $queue = new Queue(null, null, $this->makeFileInfo($dom), null);

        $deps = $this->analyzer->analyzeConfigFiles($queue, 'testvendor/own');

        $this->assertContains('magento/module-amqp', $deps);
        // db connection is disabled, so should not appear
        $this->assertNotContains('magento/module-mysql-mq', $deps);
    }

    public function testExcludesSelfPackage(): void
    {
        $dom = new DOMDocument();
        $dom->loadXML(<<<'XML'
<config>
    <topic name="self.topic" request="TestVendor\Own\Api\Something"/>
</config>
XML);

        $queue = new Queue($this->makeFileInfo($dom), null, null, null);

        $this->registry->method('getRealPackageNamespace')->willReturn('TestVendor\\Own');
        $this->registry->method('getPackageNameByNamespace')->willReturn('testvendor/own');

        $deps = $this->analyzer->analyzeConfigFiles($queue, 'testvendor/own');

        $this->assertEmpty($deps);
    }

    private function makeFileInfo(DOMDocument $dom): object
    {
        // We only need something that Queue will call getContents() on? No, Queue takes FileInfo or null.
        // For simplicity we pass a minimal object that Queue accepts (it checks for FileInfo).
        // Actually Queue expects FileInfo|null. Let's create a stub.
        $fileInfo = $this->createStub(FileInfo::class);
        $fileInfo->method('getContents')->willReturn($dom->saveXML());
        return $fileInfo;
    }
}
