<?php

declare(strict_types=1);

namespace DynamicDataImporter\Cli\Input\Mapping;

use DynamicDataImporter\Cli\Exception\CliUsageException;

final readonly class CliMappingFileLoader
{
    public function __construct(private CliMappingJsonDecoder $mappingJsonDecoder = new CliMappingJsonDecoder())
    {
    }

    /**
     * @return array<string, string>
     */
    public function load(string $mappingFile): array
    {
        if (! is_file($mappingFile) || ! is_readable($mappingFile)) {
            throw new CliUsageException(\sprintf('Mapping file "%s" was not found or is not readable.', $mappingFile));
        }

        /** @var string|false $contents */
        $contents = file_get_contents($mappingFile);
        if ($contents === false) {
            throw new CliUsageException(\sprintf('Mapping file "%s" could not be read.', $mappingFile));
        }

        return $this->mappingJsonDecoder->decode($contents, '--mapping-file');
    }
}
