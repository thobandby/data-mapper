<?php

declare(strict_types=1);

namespace DynamicDataImporter\Cli\Output;

final class CliJsonValueNormalizer
{
    public function normalize(mixed $value): mixed
    {
        $normalized = null;

        if (\is_scalar($value) || $value === null) {
            $normalized = $value;
        } elseif (\is_object($value)) {
            $normalized = $this->normalize((array) $value);
        } elseif (\is_array($value)) {
            $normalized = array_map($this->normalize(...), $value);
        }

        return $normalized;
    }
}
