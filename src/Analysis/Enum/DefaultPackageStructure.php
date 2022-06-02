<?php declare(strict_types=1);

namespace Vconnect\IntegrityChecker\Analysis\Enum;

class DefaultPackageStructure
{
    public const STRUCTURE = [
        'composer.json',
        'registration.php',
        'etc' => [
            'module.xml'
        ]
    ];
}
