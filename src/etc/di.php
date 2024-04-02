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
use Vconnect\IntegrityChecker\Domain\Package\Loader\AppCode;
use Vconnect\IntegrityChecker\Domain\Package\Loader\Vendor;
use Vconnect\IntegrityChecker\Domain\Package\LoaderChain;
use Vconnect\IntegrityChecker\Domain\Package\LoaderInterface;

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
    LoaderInterface::class => DI\autowire(LoaderChain::class)
        ->constructorParameter('loaders', [
            DI\get(AppCode::class),
            DI\get(Vendor::class),
        ]),
];
