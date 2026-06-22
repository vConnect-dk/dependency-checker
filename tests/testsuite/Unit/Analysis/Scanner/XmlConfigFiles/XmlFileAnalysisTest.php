<?php
declare(strict_types=1);

namespace Vconnect\IntegrityChecker\Tests\Unit\Analysis\Scanner\XmlConfigFiles;

use DOMDocument;
use PHPUnit\Framework\TestCase;
use Vconnect\IntegrityChecker\Analysis\Service\Dependencies\Scanner\XmlConfigFiles\XmlFileAnalysis;
use Vconnect\IntegrityChecker\Domain\PackagesRegistry;

class XmlFileAnalysisTest extends TestCase
{
    private PackagesRegistry $registry;
    private XmlFileAnalysis $analyzer;

    protected function setUp(): void
    {
        $this->registry = $this->createStub(PackagesRegistry::class);
        $this->analyzer = new XmlFileAnalysis($this->registry);
    }

    public function testGetModuleNamespaceExtractsVendorModule(): void
    {
        $this->assertSame('Magento\\Catalog', $this->analyzer->getModuleNamespace('Magento\\Catalog\\Model\\Product'));
        $this->assertSame('TestVendor\\Base', $this->analyzer->getModuleNamespace('\\TestVendor\\Base\\Api\\Data\\FooInterface'));
        $this->assertNull($this->analyzer->getModuleNamespace('SinglePart'));
        $this->assertNull($this->analyzer->getModuleNamespace(''));
    }

    public function testAnalyzeConfigDetectsHardDependenciesFromAttributes(): void
    {
        $dom = new DOMDocument();
        $dom->loadXML(<<<'XML'
<config>
    <extension_attributes for="Magento\Sales\Api\Data\OrderInterface"/>
    <attribute type="Magento\Catalog\Model\Product"/>
</config>
XML);

        $this->registry->method('getPackageNameByNamespace')->willReturnMap([
            ['Magento\\Sales', 'magento/module-sales'],
            ['Magento\\Catalog', 'magento/module-catalog'],
        ]);

        $hardMap = [
            'extension_attributes' => ['for'],
            'attribute' => ['type'],
            XmlFileAnalysis::TEXT_NODES => []
        ];

        $result = $this->analyzer->analyzeConfig([], $hardMap, $dom);

        $this->assertContains('magento/module-sales', $result);
        $this->assertContains('magento/module-catalog', $result);
    }

    public function testAnalyzeConfigSkipsSelfReferences(): void
    {
        $dom = new DOMDocument();
        $dom->loadXML('<config><preference for="TestVendor\\Own\\Class" type="TestVendor\\Own\\Implementation"/></config>');

        // Both FQCN paths resolve to the same module namespace; map that specific argument only.
        $this->registry->method('getPackageNameByNamespace')->willReturnMap([
            ['TestVendor\\Own', 'testvendor/own'],
        ]);

        $result = $this->analyzer->analyzeConfig(
            ['TestVendor\\Own'],
            ['preference' => ['for', 'type']],
            $dom
        );

        $this->assertEmpty($result);
    }

    public function testAnalyzeConfigDetectsTextNodeDependencies(): void
    {
        $dom = new DOMDocument();
        $dom->loadXML(<<<'XML'
<config xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
    <item name="some" xsi:type="object">Magento\Framework\UrlInterface</item>
</config>
XML);

        $this->registry->method('getPackageNameByNamespace')->willReturnMap([
            ['Magento\\Framework', 'magento/framework'],
        ]);

        $nodeMap = [
            XmlFileAnalysis::TEXT_NODES => ['//*[@xsi:type="object"]']
        ];

        $result = $this->analyzer->analyzeConfig([], $nodeMap, $dom);

        $this->assertContains('magento/framework', $result);
    }
}
