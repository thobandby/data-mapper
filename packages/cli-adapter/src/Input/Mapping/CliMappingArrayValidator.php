<?php

declare(strict_types=1);

namespace DynamicDataImporter\Cli\Input\Mapping;

use DynamicDataImporter\Cli\Exception\CliUsageException;

final class CliMappingArrayValidator
{
    /**
     * @param array<mixed> $decoded
     *
     * @return array<string, string>
     */
    public function validate(array $decoded, string $source): array
    {
        $mapping = [];

        foreach ($decoded as $key => $value) {
            if (! \is_string($key) || ! \is_string($value)) {
                throw new CliUsageException(\sprintf('%s must contain only string-to-string mappings.', $source));
            }

            $mapping[$key] = $value;
        }

        return $mapping;
    }
}
