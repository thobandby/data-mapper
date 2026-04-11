<?php

declare(strict_types=1);

namespace DynamicDataImporter\Cli\Output;

use DynamicDataImporter\Cli\Input\CliOptions;

final readonly class CliTextPayloadFormatter
{
    public function __construct(
        private CliAnalyzeTextFormatter $analyzeFormatter,
        private CliPreviewTextFormatter $previewFormatter,
        private CliExecuteTextFormatter $executeFormatter,
    ) {
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function format(CliOptions $options, array $payload): ?string
    {
        return match ($options->action) {
            'analyze' => $this->analyzeFormatter->format($payload),
            'preview' => $this->previewFormatter->format($payload),
            'execute' => $this->executeFormatter->format($payload, $options),
            default => null,
        };
    }
}
