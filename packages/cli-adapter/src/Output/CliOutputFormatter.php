<?php

declare(strict_types=1);

namespace DynamicDataImporter\Cli\Output;

use DynamicDataImporter\Cli\Contract\OutputFormatterInterface;
use DynamicDataImporter\Cli\Exception\CliUsageException;
use DynamicDataImporter\Cli\Input\CliOptions;

final readonly class CliOutputFormatter implements OutputFormatterInterface
{
    public function __construct(
        private CliUsageFormatter $usageFormatter,
        private CliTextPayloadFormatter $textPayloadFormatter,
        private CliJsonValueNormalizer $jsonValueNormalizer,
    ) {
    }

    public function usage(): string
    {
        return $this->usageFormatter->build();
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function formatPayload(CliOptions $options, array $payload): string
    {
        if ($options->responseFormat === 'json') {
            return $this->encodeJson($payload) . "\n";
        }

        return $this->textPayloadFormatter->format($options, $payload) ?? $this->encodeJson($payload) . "\n";
    }

    public function encodeJson(mixed $value): string
    {
        try {
            return json_encode(
                $this->jsonValueNormalizer->normalize($value),
                \JSON_PRETTY_PRINT | \JSON_UNESCAPED_UNICODE | \JSON_THROW_ON_ERROR,
            );
        } catch (\JsonException $exception) {
            throw new CliUsageException('Unable to encode CLI output as JSON.', 0, $exception);
        }
    }
}
