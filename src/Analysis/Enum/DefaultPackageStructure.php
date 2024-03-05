<?php declare(strict_types=1);

namespace Vconnect\IntegrityChecker\Analysis\Enum;

class DefaultPackageStructure
{
    public const STRUCTURE = [
        'composer.json' => 'composer.json',
        'registration.php' => 'registration.php',
        'etc' => [
            'module.xml' => 'module.xml'
        ]
    ];
}
