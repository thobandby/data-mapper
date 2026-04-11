<?php

declare(strict_types=1);

namespace DynamicDataImporter\Infrastructure\Api;

final readonly class ApiPostDataReader
{
    /**
     * @param array<string, mixed> $post
     */
    public function __construct(
        private array $post,
    ) {
    }

    public function string(string $key, string $default = ''): string
    {
        $value = $this->post[$key] ?? $default;

        return is_string($value) ? trim($value) : $default;
    }

    public function nullableString(string $key): ?string
    {
        $value = $this->string($key);

        return $value !== '' ? $value : null;
    }

    public function int(string $key, int $default): int
    {
        $value = $this->post[$key] ?? null;
        if ($value === null || $value === '') {
            return $default;
        }

        return (int) $value;
    }
}
