<?php

declare(strict_types=1);

return static function (array $extraExclude = []): array {
    $config = require dirname(__DIR__, 2) . '/tools/phpinsights/base-config.php';

    if ($extraExclude !== []) {
        $config['exclude'] = array_values(array_unique([
            ...$config['exclude'],
            ...$extraExclude,
        ]));
    }

    return $config;
};
