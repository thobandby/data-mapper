<?php

declare(strict_types=1);

namespace DynamicDataImporter\Cli\Contract;

use DynamicDataImporter\Cli\Input\CliOptions;

interface OutputFormatterInterface
{
    public function usage(): string;

    /**
     * @param array<string, mixed> $payload
     */
    public function formatPayload(CliOptions $options, array $payload): string;

    public function encodeJson(mixed $value): string;
}
