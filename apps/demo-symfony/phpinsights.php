<?php

declare(strict_types=1);

use NunoMaduro\PhpInsights\Domain\Insights\CyclomaticComplexityIsHigh;
use NunoMaduro\PhpInsights\Domain\Insights\ForbiddenNormalClasses;
use SlevomatCodingStandard\Sniffs\Functions\FunctionLengthSniff;

$makeConfig = require dirname(__DIR__, 2).'/tools/phpinsights/make-config.php';

$config = $makeConfig();
$config['remove'][] = CyclomaticComplexityIsHigh::class;
$config['remove'][] = ForbiddenNormalClasses::class;
$config['remove'][] = FunctionLengthSniff::class;

return $config;
