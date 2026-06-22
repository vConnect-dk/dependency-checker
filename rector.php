<?php

declare(strict_types=1);

use Rector\CodeQuality\Rector\ClassMethod\OptionalParametersAfterRequiredRector;
use Rector\CodeQuality\Rector\If_\ExplicitBoolCompareRector;
use Rector\Config\RectorConfig;
use Rector\Php74\Rector\Assign\NullCoalescingOperatorRector;
use Rector\Php80\Rector\Class_\StringableForToStringRector;
use Rector\Php80\Rector\ClassMethod\FinalPrivateToPrivateVisibilityRector;
use Rector\Php80\Rector\ClassMethod\SetStateToStaticRector;
use Rector\Php84\Rector\Param\ExplicitNullableParamTypeRector;
use Rector\TypeDeclaration\Rector\StmtsAwareInterface\DeclareStrictTypesRector;

return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/src',
        __DIR__ . '/bin',
    ])
    ->withPhpSets(php84: true)
    ->withRules([
        ExplicitNullableParamTypeRector::class,
        FinalPrivateToPrivateVisibilityRector::class,
        OptionalParametersAfterRequiredRector::class,
        SetStateToStaticRector::class,
        StringableForToStringRector::class,
        NullCoalescingOperatorRector::class,
        DeclareStrictTypesRector::class,
    ])
    ->withPreparedSets(
        deadCode: true,
        codeQuality: true,
        typeDeclarations: true,
        privatization: true,
        earlyReturn: true,
        strictBooleans: false,
    )
    ->withSkip([
        ExplicitBoolCompareRector::class,
    ]);
