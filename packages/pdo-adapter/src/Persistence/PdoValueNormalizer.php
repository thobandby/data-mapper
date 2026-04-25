<?php

declare(strict_types=1);

namespace DynamicDataImporter\Pdo\Persistence;

final class PdoValueNormalizer
{
    public function normalize(mixed $value): bool|float|int|string|null
    {
        return match (get_debug_type($value)) {
            'bool', 'int', 'float', 'string', 'null' => $value,
            default => $this->normalizeComplexValue($value),
        };
    }

    private function normalizeComplexValue(mixed $value): string
    {
        if ($value instanceof \BackedEnum) {
            return (string) $value->value;
        }

        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d H:i:s');
        }

        if ($value instanceof \Stringable) {
            return (string) $value;
        }

        return json_encode($value, JSON_THROW_ON_ERROR);
    }
}
