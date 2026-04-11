<?php

declare(strict_types=1);

return [
    '@Symfony' => true,

    'yoda_style' => false,
    'not_operator_with_successor_space' => true,
    'array_syntax' => [
        'syntax' => 'short',
    ],

    'binary_operator_spaces' => [
        'default' => 'single_space',
    ],

    'blank_line_before_statement' => [
        'statements' => ['return'],
    ],

    'concat_space' => [
        'spacing' => 'one',
    ],

    'no_unused_imports' => true,

    'ordered_imports' => [
        'sort_algorithm' => 'alpha',
    ],

    'single_quote' => true,
];
