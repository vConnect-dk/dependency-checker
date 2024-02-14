<?php
declare(strict_types=1);

use Vconnect\IntegrityChecker\Analysis\Service\Dependencies\Scanner\{DbSchema,
    GraphQlSchema,
    PhpFiles,
    QueueConfig,
    XmlConfigFiles};
use Vconnect\IntegrityChecker\Analysis\Service\Dependencies\ScannerPool;
use Vconnect\IntegrityChecker\Domain\GraphQlSchemaStitching\GraphQlReader\Reader\{EnumType,
    InputObjectType,
    InterfaceType,
    ObjectType,
    UnionType};
use Vconnect\IntegrityChecker\Domain\GraphQlSchemaStitching\GraphQlReader\TypeReaderComposite;

return [
    TypeReaderComposite::class => DI\autowire()
        ->constructorParameter('typeReaders', [
            DI\get(EnumType::class),
            DI\get(InputObjectType::class),
            DI\get(InterfaceType::class),
            DI\get(ObjectType::class),
            DI\get(UnionType::class),
        ]),
    ScannerPool::class => DI\autowire()
        ->constructorParameter('scanners', [
            DI\get(PhpFiles::class),
            DI\get(XmlConfigFiles::class),
            DI\get(DbSchema::class),
            DI\get(QueueConfig::class),
            DI\get(GraphQlSchema::class)
        ])
];