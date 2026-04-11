<?php

declare(strict_types=1);

namespace DynamicDataImporter\Infrastructure\Security;

final class SpreadsheetFormulaSanitizer
{
    public function sanitize(mixed $value): mixed
    {
        if (is_array($value)) {
            return $this->sanitizeArray($value);
        }

        if (! is_string($value)) {
            return $value;
        }

        return $this->sanitizeString($value);
    }

    /**
     * @param list<array<string, mixed>> $sample
     *
     * @return list<array<string, mixed>>
     */
    public function sanitizeSample(array $sample): array
    {
        $sanitized = [];

        foreach ($sample as $row) {
            /* @var array<string, mixed> $row */
            $sanitized[] = $this->sanitize($row);
        }

        return $sanitized;
    }

    /**
     * @param array<array-key, mixed> $value
     *
     * @return array<array-key, mixed>
     */
    private function sanitizeArray(array $value): array
    {
        $sanitized = [];

        foreach ($value as $key => $item) {
            $sanitized[$key] = $this->sanitize($item);
        }

        return $sanitized;
    }

    private function sanitizeString(string $value): string
    {
        return preg_match('/^[\s]*[=+\-@]/', $value) === 1
            ? "'" . $value
            : $value;
    }
}
