<?php

declare(strict_types=1);

use PhpCsFixer\Config;
use PhpCsFixer\Finder;

return static function (
    string $projectDir,
    array $dirs = ['src', 'tests'],
    array $appendFiles = []
): Config {
    $rules = require dirname(__DIR__, 2) . '/tools/php-cs-fixer/rules.php';

    $finder = Finder::create();

    $existingDirs = [];
    foreach ($dirs as $dir) {
        $path = $projectDir . '/' . $dir;
        if (is_dir($path)) {
            $existingDirs[] = $path;
        }
    }

    if ($existingDirs !== []) {
        $finder->in($existingDirs);
    }

    $existingFiles = [];
    foreach ($appendFiles as $file) {
        $path = $projectDir . '/' . $file;
        if (is_file($path)) {
            $existingFiles[] = $path;
        }
    }

    if ($existingFiles !== []) {
        $finder->append($existingFiles);
    }

    return (new Config())
        ->setRiskyAllowed(false)
        ->setRules($rules)
        ->setFinder($finder);
};
