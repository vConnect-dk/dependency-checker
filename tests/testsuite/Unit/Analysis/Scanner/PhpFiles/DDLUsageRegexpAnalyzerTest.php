<?php
declare(strict_types=1);

namespace Vconnect\IntegrityChecker\Tests\Unit\Analysis\Scanner\PhpFiles;

use PHPUnit\Framework\TestCase;
use Vconnect\IntegrityChecker\Analysis\Service\Dependencies\Scanner\PhpFiles\DDLUsageRegexpAnalyzer;

class DDLUsageRegexpAnalyzerTest extends TestCase
{
    private DDLUsageRegexpAnalyzer $analyzer;

    protected function setUp(): void
    {
        $this->analyzer = new DDLUsageRegexpAnalyzer();
    }

    public function testDetectsGetTableCalls(): void
    {
        $content = <<<'PHP'
<?php
class Foo {
    public function bar() {
        $t = $this->getTable('catalog_product_entity');
        $t2 = $this->getTableName("sales_order");
    }
}
PHP;

        $tables = $this->analyzer->getTablesUsed($content);

        $this->assertContains('catalog_product_entity', $tables);
        $this->assertContains('sales_order', $tables);
    }

    public function testReturnsEmptyWhenNoMatches(): void
    {
        $content = '<?php $x = 1;';
        $this->assertSame([], $this->analyzer->getTablesUsed($content));
    }
}
