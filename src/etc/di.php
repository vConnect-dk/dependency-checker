<?php
declare(strict_types=1);

use Vconnect\IntegrityChecker\Analysis\Service\Dependencies\Scanner\{DbDDL,
    DbSchema,
    GraphQlSchema,
    Layouts,
    PhpFiles,
    QueueConfig,
    Routes,
    ScannerResult\ScannerResult,
    ScannerResult\ScannerResultInterface,
    XmlConfigFiles};
use Vconnect\IntegrityChecker\Analysis\Service\Dependencies\ScannerPool;

return [
    ScannerPool::class => DI\autowire()
        ->constructorParameter('scanners', [
            DI\get(PhpFiles::class),
            DI\get(XmlConfigFiles::class),
            DI\get(DbSchema::class),
            DI\get(QueueConfig::class),
            DI\get(GraphQlSchema::class),
            DI\get(DbDDL::class),
            DI\get(Routes::class),
            DI\get(Layouts::class),
        ]),
    ScannerResultInterface::class => DI\create(ScannerResult::class),
];