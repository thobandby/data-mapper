<?php

declare(strict_types=1);

namespace DynamicDataImporter\Cli\Contract;

use DynamicDataImporter\Cli\Input\CliOptions;

interface ArtifactWriterInterface
{
    /**
     * @param array<string, mixed> $payload
     */
    public function write(array $payload, CliOptions $options): void;
}
