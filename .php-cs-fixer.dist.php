<?php

declare(strict_types=1);

$makeConfig = require __DIR__ . '/tools/php-cs-fixer/make-config.php';

return $makeConfig(
    __DIR__,
    [
        'packages/core/src',
        'packages/core/tests',
        'packages/cli-adapter/src',
        'packages/cli-adapter/tests',
        'packages/doctrine-adapter/src',
        'packages/doctrine-adapter/tests',
        'packages/symfony-adapter/src',
        'packages/symfony-adapter/tests',
        'packages/symfony-adapter/config',
        'apps/demo-symfony/src',
        'apps/demo-symfony/tests',
        'apps/demo-symfony/config',
        'apps/demo-cli/src',
        'apps/demo-cli/tests',
    ],
    [
        'packages/core/bin/api/index.php',
        'packages/cli-adapter/bin/import',
        'apps/demo-symfony/bin/console',
        'apps/demo-cli/bin/import',
    ]
);
