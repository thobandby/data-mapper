<?php

declare(strict_types=1);

namespace DynamicDataImporter\Cli\Input\Mapping;

use DynamicDataImporter\Cli\Exception\CliUsageException;

final class CliMapEntryParser
{
    /**
     * @return array{0: string, 1: string}
     */
    public function parse(string $value): array
    {
        $parts = explode('=', $value, 2);

        if (\count($parts) !== 2 || $parts[0] === '' || $parts[1] === '') {
            throw new CliUsageException('Option --map must use the form source=target.');
        }

        return [$parts[0], $parts[1]];
    }
}
