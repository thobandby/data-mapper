<?php

declare(strict_types=1);

use NunoMaduro\PhpInsights\Domain\Insights\ForbiddenSecurityIssues;
use PHP_CodeSniffer\Standards\Generic\Sniffs\Files\LineLengthSniff;
use SlevomatCodingStandard\Sniffs\Classes\SuperfluousExceptionNamingSniff;
use SlevomatCodingStandard\Sniffs\Classes\SuperfluousInterfaceNamingSniff;
use SlevomatCodingStandard\Sniffs\ControlStructures\DisallowYodaComparisonSniff;
use SlevomatCodingStandard\Sniffs\TypeHints\DisallowMixedTypeHintSniff;

return [
    'preset' => 'symfony',

    'exclude' => [
        'vendor',
        'var',
        'node_modules',
        'public/build',
    ],

    'remove' => [
        ForbiddenSecurityIssues::class,
        DisallowMixedTypeHintSniff::class,
        SuperfluousInterfaceNamingSniff::class,
        SuperfluousExceptionNamingSniff::class,
    ],

    'config' => [
        LineLengthSniff::class => [
            'lineLimit' => 140,
            'absoluteLineLimit' => 180,
            'ignoreComments' => true,
        ],
        DisallowYodaComparisonSniff::class => [
            'always' => false,
            'identical' => false,
            'equal' => false,
            'lessAndGreater' => false,
        ],
    ],

    'requirements' => [
        'min-quality' => 0,
        'min-complexity' => 0,
        'min-architecture' => 0,
        'min-style' => 0,
    ],
];
