<?php

declare(strict_types=1);

namespace DynamicDataImporter\Cli\Output;

final readonly class CliExecutionOutputValueFormatter
{
    private const EMPTY_LABEL = '(empty)';

    private \Closure $encodeJson;

    public function __construct(callable $encodeJson)
    {
        $this->encodeJson = \Closure::fromCallable($encodeJson);
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function format(array $payload): string
    {
        if ($payload['output_format'] === 'sql') {
            $sql = (string) ($payload['output']['sql'] ?? '');

            return $sql !== '' ? $sql : self::EMPTY_LABEL;
        }

        $rows = $payload['output']['rows'] ?? [];

        return $rows === [] ? self::EMPTY_LABEL : ($this->encodeJson)($rows);
    }
}
