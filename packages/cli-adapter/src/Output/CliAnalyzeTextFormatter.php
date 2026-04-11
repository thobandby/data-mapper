<?php

declare(strict_types=1);

namespace DynamicDataImporter\Cli\Output;

final readonly class CliAnalyzeTextFormatter
{
    private \Closure $stdout;

    public function __construct(callable $stdout, private CliSampleTableRenderer $sampleTableRenderer)
    {
        $this->stdout = \Closure::fromCallable($stdout);
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function format(array $payload): string
    {
        ($this->stdout)(implode("\n", [
            ...CliFileMetadataFormatter::format($payload),
            sprintf('Headers: %s', implode(', ', $payload['headers'])),
            'Sample:',
        ]) . "\n");
        $this->sampleTableRenderer->render($payload['headers'], $payload['sample']);

        return '';
    }
}
