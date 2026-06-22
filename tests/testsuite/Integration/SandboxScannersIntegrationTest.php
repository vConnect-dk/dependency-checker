<?php
declare(strict_types=1);

namespace Vconnect\IntegrityChecker\Tests\Integration;

use Vconnect\IntegrityChecker\Analysis\Service\Dependencies\Scanner\DbDDL;
use Vconnect\IntegrityChecker\Analysis\Service\Dependencies\Scanner\DbSchema;
use Vconnect\IntegrityChecker\Analysis\Service\Dependencies\Scanner\Layouts;
use Vconnect\IntegrityChecker\Analysis\Service\Dependencies\Scanner\PhpFiles;
use Vconnect\IntegrityChecker\Analysis\Service\Dependencies\Scanner\QueueConfig;
use Vconnect\IntegrityChecker\Analysis\Service\Dependencies\Scanner\XmlConfigFiles;

/**
 * Real-workload integration tests: each scanner runs against TestVendor/* fixtures in tests/sandbox/app/code
 * and we assert on concrete dependency package names discovered from those files.
 *
 * Covered scanner groups:
 *  - DbSchema      (Dependent etc/db_schema.xml FK → Base table)
 *  - PhpFiles      (PHP + PHTML class references; plugin subject as soft)
 *  - XmlConfigFiles (di.xml / extension_attributes / system.xml)
 *  - QueueConfig    (queue_consumer.xml connection=db + handler resolution paths)
 *  - Layouts        (layout block class / template from Base)
 *  - DbDDL          (getTable() usage collected during PhpFiles event observers)
 */
class SandboxScannersIntegrationTest extends SandboxIntegrationTestCase
{
    public function testDbSchemaDetectsBaseFromForeignKeyOnDependent(): void
    {
        $package = $this->getFixturePackage(self::FIXTURE_DEPENDENT);

        /** @var DbSchema $scanner */
        $scanner = $this->container->get(DbSchema::class);
        $result = $scanner->lookupDependencies($package);

        // FK to testvendor_base_entity + table ownership maps to testvendor/base.
        // DbSchema currently surfaces FK/table ownership as soft dependencies.
        $soft = $this->softDeps($result);
        $this->assertContains(
            self::FIXTURE_BASE,
            $soft,
            'DbSchema should report testvendor/base from Dependent db_schema.xml '
            . '(FK to testvendor_base_entity). Got soft=' . json_encode($soft)
        );
    }

    public function testPhpFilesDetectsBaseFromPhpAndPhtmlOnDependent(): void
    {
        $package = $this->getFixturePackage(self::FIXTURE_DEPENDENT);

        /** @var PhpFiles $scanner */
        $scanner = $this->container->get(PhpFiles::class);
        $result = $scanner->lookupDependencies($package);

        $hard = $this->hardDeps($result);
        $soft = $this->softDeps($result);

        // UsesBase.php, call_base.phtml, RouteCaller.php etc. reference TestVendor\Base classes (hard).
        $this->assertContains(
            self::FIXTURE_BASE,
            $hard,
            'PhpFiles should report hard dep testvendor/base from PHP/PHTML in Dependent. Got hard='
            . json_encode($hard)
        );

        // SomeServicePlugin targets Base subject declared as plugin → classified as soft.
        $this->assertContains(
            self::FIXTURE_BASE,
            $soft,
            'PhpFiles should report soft dep testvendor/base for plugin subject. Got soft='
            . json_encode($soft)
        );
    }

    public function testXmlConfigFilesDetectsBaseFromDiAndExtensionAttributesOnDependent(): void
    {
        $package = $this->getFixturePackage(self::FIXTURE_DEPENDENT);

        /** @var XmlConfigFiles $scanner */
        $scanner = $this->container->get(XmlConfigFiles::class);
        $result = $scanner->lookupDependencies($package);

        $hard = $this->hardDeps($result);
        $soft = $this->softDeps($result);

        // Hard: extension_attributes for=TestVendor\Base\Api\Data\SampleInterface,
        //       system.xml source_model, di.xml xsi:type="object" argument.
        $this->assertContains(
            self::FIXTURE_BASE,
            $hard,
            'XmlConfigFiles should report hard dep testvendor/base from extension_attributes/system/di. Got hard='
            . json_encode($hard)
        );

        // Soft: di.xml preference/type/plugin references to Base types.
        $this->assertContains(
            self::FIXTURE_BASE,
            $soft,
            'XmlConfigFiles should report soft dep testvendor/base from di.xml preference/type/plugin. Got soft='
            . json_encode($soft)
        );
    }

    public function testQueueConfigDetectsMysqlMqFromDependentConsumerAndBasePublisher(): void
    {
        $dependent = $this->getFixturePackage(self::FIXTURE_DEPENDENT);
        $base = $this->getFixturePackage(self::FIXTURE_BASE);

        /** @var QueueConfig $scanner */
        $scanner = $this->container->get(QueueConfig::class);

        $dependentHard = $this->hardDeps($scanner->lookupDependencies($dependent));
        $baseHard = $this->hardDeps($scanner->lookupDependencies($base));

        // Dependent queue_consumer.xml uses connection="db" → magento/module-mysql-mq
        $this->assertContains(
            'magento/module-mysql-mq',
            $dependentHard,
            'QueueConfig on Dependent should require magento/module-mysql-mq for connection=db. Got='
            . json_encode($dependentHard)
        );

        // Base queue_publisher.xml publishes with connection name="db"
        $this->assertContains(
            'magento/module-mysql-mq',
            $baseHard,
            'QueueConfig on Base should require magento/module-mysql-mq for publisher connection db. Got='
            . json_encode($baseHard)
        );
    }

    public function testLayoutsDetectsBaseBlockClassAndTemplateOnDependent(): void
    {
        $package = $this->getFixturePackage(self::FIXTURE_DEPENDENT);

        /** @var Layouts $scanner */
        $scanner = $this->container->get(Layouts::class);
        $result = $scanner->lookupDependencies($package);

        $hard = $this->hardDeps($result);
        $soft = $this->softDeps($result);

        // default.xml: <block class="TestVendor\Base\Block\SampleBlock" .../>
        $this->assertContains(
            self::FIXTURE_BASE,
            $hard,
            'Layouts should report hard dep testvendor/base from block class in Dependent layout. Got hard='
            . json_encode($hard)
        );

        // default.xml: template="TestVendor_Base::sample.phtml"
        $this->assertContains(
            self::FIXTURE_BASE,
            $soft,
            'Layouts should report soft dep testvendor/base from module template reference. Got soft='
            . json_encode($soft)
        );
    }

    public function testDbDdlDetectsBaseTableUsageAfterPhpFilesScanOnDependent(): void
    {
        $package = $this->getFixturePackage(self::FIXTURE_DEPENDENT);

        // DbDDL depends on observers fired during PhpFiles analysis (getTable / getTableName).
        /** @var PhpFiles $phpFiles */
        $phpFiles = $this->container->get(PhpFiles::class);
        $phpFiles->lookupDependencies($package);

        /** @var DbDDL $scanner */
        $scanner = $this->container->get(DbDDL::class);
        $result = $scanner->lookupDependencies($package);

        $hard = $this->hardDeps($result);
        $this->assertContains(
            self::FIXTURE_BASE,
            $hard,
            'DbDDL should report hard dep testvendor/base from TableUser getTable(\'testvendor_base_entity\'). Got hard='
            . json_encode($hard)
        );
    }

    public function testCleanModulePhpFilesStillResolvesDeclaredBaseUsage(): void
    {
        $package = $this->getFixturePackage(self::FIXTURE_CLEAN);

        /** @var PhpFiles $scanner */
        $scanner = $this->container->get(PhpFiles::class);
        $result = $scanner->lookupDependencies($package);

        // CleanUser.php uses SomeService from Base; scanner should still see the runtime dependency.
        $hard = $this->hardDeps($result);
        $this->assertContains(
            self::FIXTURE_BASE,
            $hard,
            'PhpFiles on Clean should still detect usage of testvendor/base (declared correctly elsewhere). Got hard='
            . json_encode($hard)
        );
    }
}
