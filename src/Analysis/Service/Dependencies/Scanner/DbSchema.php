<?php
declare(strict_types=1);

namespace Vconnect\IntegrityChecker\Analysis\Service\Dependencies\Scanner;

use Vconnect\IntegrityChecker\Analysis\Service\Dependencies\Scanner\DbSchema\ModulesSchemaCollector;
use Vconnect\IntegrityChecker\Analysis\Service\Dependencies\Scanner\ScannerResult\ScannerResult;
use Vconnect\IntegrityChecker\Analysis\Service\Dependencies\Scanner\ScannerResult\ScannerResultInterface;
use Vconnect\IntegrityChecker\Domain\Package;

class DbSchema implements DependenciesScannerInterface
{
    private XmlFileAnalysis $xmlFileAnalysis;
    private ModulesSchemaCollector $schemaCollector;

    public function __construct()
    {
        $this->xmlFileAnalysis = new XmlFileAnalysis();
        $this->schemaCollector = new ModulesSchemaCollector();
    }

    /**
     * Search for dependencies in .xml inside the module directory.
     *
     * @param Package $package
     *
     * @return ScannerResultInterface - list of packages founded as dependencies inside package's files.
     */
    public function lookupDependencies(Package $package): ScannerResultInterface
    {
        $scannerResult = new ScannerResult();
        // TODO
        // parse package db_schema.xml
        // get refferenced tables using $this->schemaCollector->getSchemaOwnerPackageName("sometable");
        // submit dependencies to ScannerResult
        return $scannerResult;
    }
}
