<?php

declare(strict_types=1);

namespace DynamicDataImporter\Infrastructure\Persistence;

final class OutputDirectoryInitializer
{
    public function ensureFor(string $outputFile): void
    {
        $directory = dirname($outputFile);
        if (! is_dir($directory) && ! mkdir($directory, 0777, true) && ! is_dir($directory)) {
            throw new \InvalidArgumentException("Directory '{$directory}' could not be created.");
        }
    }
}
