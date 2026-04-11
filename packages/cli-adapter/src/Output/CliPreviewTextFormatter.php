<?php

declare(strict_types=1);

namespace DynamicDataImporter\Cli\Output;

use DynamicDataImporter\Cli\Application\CliApplication;

final readonly class CliPreviewTextFormatter
{
    private \Closure $stdout;
    private \Closure $encodeJson;

    public function __construct(callable $stdout, private CliSampleTableRenderer $sampleTableRenderer, callable $encodeJson)
    {
        $this->stdout = \Closure::fromCallable($stdout);
        $this->encodeJson = \Closure::fromCallable($encodeJson);
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function format(array $payload): string
    {
        ($this->stdout)(implode("\n", [
            ...CliFileMetadataFormatter::format($payload),
            sprintf('Original headers: %s', implode(', ', $payload['original_headers'])),
            sprintf('Mapped headers: %s', implode(', ', $payload['mapped_headers'])),
            sprintf('Mapping: %s', $payload['mapping'] === [] ? CliApplication::NONE_LABEL : ($this->encodeJson)($payload['mapping'])),
            'Sample:',
        ]) . "\n");
        $this->sampleTableRenderer->render($payload['mapped_headers'], $payload['sample']);

        return '';
    }
}
