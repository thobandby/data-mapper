<?php

declare(strict_types=1);

namespace DynamicDataImporter\Cli\Infrastructure\Filesystem;

use DynamicDataImporter\Cli\Contract\OutputFormatterInterface;

final readonly class CliArtifactContentBuilder
{
    public function __construct(private OutputFormatterInterface $formatter)
    {
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function build(array $payload): string
    {
        return $payload['output_format'] === 'sql'
            ? (string) ($payload['output']['sql'] ?? '')
            : $this->formatter->encodeJson($payload['output']['rows'] ?? []) . "\n";
    }
}
