<?php

declare(strict_types=1);

namespace DynamicDataImporter\Infrastructure\Reader;

use DynamicDataImporter\Domain\Exception\ImporterException;

final class FileContentLoader
{
    public function load(string $filePath): string
    {
        if (! is_file($filePath) || ! is_readable($filePath)) {
            throw ImporterException::cannotOpenFile($filePath);
        }

        $content = file_get_contents($filePath);
        if ($content === false) {
            throw ImporterException::cannotReadFile($filePath);
        }

        return $content;
    }
}
