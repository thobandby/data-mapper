<?php

declare(strict_types=1);

namespace App\Import\Status;

final class ImportJobSerializer
{
    /**
     * @param array<string, mixed> $payload
     */
    public function encode(array $payload): string
    {
        return (string) json_encode($payload, JSON_THROW_ON_ERROR);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function decode(?string $payload): ?array
    {
        if (! is_string($payload)) {
            return null;
        }

        $decoded = json_decode($payload, true, 512, JSON_THROW_ON_ERROR);

        return is_array($decoded) ? $decoded : null;
    }
}
