<?php

declare(strict_types=1);

namespace DynamicDataImporter\Cli\Input\Mapping;

use DynamicDataImporter\Cli\Exception\CliUsageException;
use JsonException as NativeJsonException;

final class CliMappingJsonDecoder
{
    public function __construct(private readonly CliMappingArrayValidator $arrayValidator = new CliMappingArrayValidator())
    {
    }

    /**
     * @return array<string, string>
     */
    public function decode(string $json, string $source): array
    {
        try {
            $decoded = \json_decode($json, true, 512, \JSON_THROW_ON_ERROR);
        } catch (NativeJsonException $exception) {
            throw new CliUsageException(\sprintf('%s must contain a valid JSON object.', $source), 0, $exception);
        }

        if (! \is_array($decoded)) {
            throw new CliUsageException(\sprintf('%s must decode to a JSON object.', $source));
        }

        return $this->arrayValidator->validate($decoded, $source);
    }
}
