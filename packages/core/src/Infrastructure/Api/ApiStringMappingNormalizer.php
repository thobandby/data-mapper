<?php

declare(strict_types=1);

namespace DynamicDataImporter\Infrastructure\Api;

final class ApiStringMappingNormalizer
{
    /**
     * @param array<array-key, mixed> $mapping
     *
     * @return array<string, string>
     */
    public function normalize(array $mapping): array
    {
        $normalizedMapping = [];

        foreach ($mapping as $source => $target) {
            $normalizedMapping[$this->normalizeKey($source)] = $this->normalizeValue($target);
        }

        return $normalizedMapping;
    }

    private function normalizeKey(mixed $source): string
    {
        if (is_string($source)) {
            return $source;
        }

        throw new \InvalidArgumentException('Mapping entries must be non-empty strings.');
    }

    private function normalizeValue(mixed $target): string
    {
        if (is_string($target) && $target !== '') {
            return $target;
        }

        throw new \InvalidArgumentException('Mapping entries must be non-empty strings.');
    }
}
