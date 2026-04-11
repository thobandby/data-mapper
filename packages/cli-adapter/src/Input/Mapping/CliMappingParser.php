<?php

declare(strict_types=1);

namespace DynamicDataImporter\Cli\Input\Mapping;

final class CliMappingParser
{
    private readonly CliMappingFileLoader $mappingFileLoader;
    private readonly CliMappingJsonDecoder $mappingJsonDecoder;

    public function __construct(
        ?CliMappingFileLoader $mappingFileLoader = null,
        ?CliMappingJsonDecoder $mappingJsonDecoder = null,
    ) {
        $this->mappingJsonDecoder = $mappingJsonDecoder ?? new CliMappingJsonDecoder();
        $this->mappingFileLoader = $mappingFileLoader ?? new CliMappingFileLoader($this->mappingJsonDecoder);
    }

    /**
     * @param list<array{0: string, 1: string}> $maps
     *
     * @return array<string, string>
     */
    public function buildMapping(?string $mappingFile, ?string $mappingJson, array $maps): array
    {
        $mapping = $mappingFile === null ? [] : $this->mappingFileLoader->load($mappingFile);

        if ($mappingJson !== null) {
            $mapping = array_replace($mapping, $this->mappingJsonDecoder->decode($mappingJson, '--mapping-json'));
        }

        foreach ($maps as [$source, $target]) {
            $mapping[$source] = $target;
        }

        return $mapping;
    }
}
