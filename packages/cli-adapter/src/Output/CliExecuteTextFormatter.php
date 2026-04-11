<?php

declare(strict_types=1);

namespace DynamicDataImporter\Cli\Output;

use DynamicDataImporter\Cli\Input\CliOptions;

final readonly class CliExecuteTextFormatter
{
    public function __construct(
        private CliExecutionLineBuilder $lineBuilder,
        private CliExecutionOutputValueFormatter $outputValueFormatter,
    ) {
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function format(array $payload, CliOptions $options): string
    {
        /** @var array<string, mixed> $result */
        $result = $payload['result'];

        return implode("\n", [
            ...$this->lineBuilder->build($payload, $options, $result),
            'Output:',
            $this->outputValueFormatter->format($payload),
        ]) . "\n";
    }
}
