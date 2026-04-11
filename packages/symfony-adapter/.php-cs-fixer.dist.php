<?php

declare(strict_types=1);

$makeConfig = require dirname(__DIR__, 2) . '/tools/php-cs-fixer/make-config.php';

return $makeConfig(__DIR__, ['src', 'tests', 'config']);
