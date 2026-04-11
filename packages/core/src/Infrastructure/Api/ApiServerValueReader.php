<?php

declare(strict_types=1);

namespace DynamicDataImporter\Infrastructure\Api;

final readonly class ApiServerValueReader
{
    /**
     * @param array<string, mixed> $server
     */
    public function __construct(
        private array $server,
    ) {
    }

    public function string(string $key, string $default = ''): string
    {
        $value = $this->server[$key] ?? $default;

        return is_string($value) ? $value : $default;
    }

    public function nullableString(string $key): ?string
    {
        $value = $this->server[$key] ?? null;

        return is_string($value) && $value !== '' ? $value : null;
    }
}
