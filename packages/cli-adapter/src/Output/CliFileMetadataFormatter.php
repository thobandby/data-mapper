<?php

declare(strict_types=1);

namespace DynamicDataImporter\Cli\Output;

use DynamicDataImporter\Cli\Application\CliApplication;

final class CliFileMetadataFormatter
{
    /**
     * @param array<string, mixed> $payload
     *
     * @return list<string>
     */
    public static function format(array $payload): array
    {
        return [
            sprintf(CliApplication::FILE_TYPE_LINE, (string) $payload['file_type']),
            sprintf(CliApplication::DELIMITER_LINE, $payload['delimiter'] ?? CliApplication::NONE_LABEL),
        ];
    }
}
