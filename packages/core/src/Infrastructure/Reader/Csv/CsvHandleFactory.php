<?php

declare(strict_types=1);

namespace DynamicDataImporter\Infrastructure\Reader\Csv;

use DynamicDataImporter\Domain\Exception\ImporterException;

final class CsvHandleFactory
{
    /**
     * @return resource
     */
    public function open(string $filePath)
    {
        if (! is_file($filePath) || ! is_readable($filePath)) {
            throw ImporterException::cannotOpenFile($filePath);
        }

        $handle = fopen($filePath, 'rb');
        if ($handle === false) {
            throw ImporterException::cannotOpenFile($filePath);
        }

        return $handle;
    }
}
