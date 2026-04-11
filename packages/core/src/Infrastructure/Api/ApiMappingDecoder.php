<?php

declare(strict_types=1);

namespace DynamicDataImporter\Infrastructure\Api;

final class ApiMappingDecoder
{
    private readonly ApiStringMappingNormalizer $normalizer;

    public function __construct()
    {
        $this->normalizer = new ApiStringMappingNormalizer();
    }

    /**
     * @param array<string, mixed> $post
     *
     * @return array<string, string>
     */
    public function decode(array $post): array
    {
        $mappingJson = $this->mappingJson($post);
        if ($mappingJson === null) {
            return [];
        }

        return $this->normalizer->normalize($this->decodeJson($mappingJson));
    }

    /**
     * @param array<string, mixed> $post
     */
    private function mappingJson(array $post): ?string
    {
        $mappingJson = $post['mapping'] ?? null;

        return is_string($mappingJson) && $mappingJson !== '' ? $mappingJson : null;
    }

    /**
     * @return array<array-key, mixed>
     */
    private function decodeJson(string $mappingJson): array
    {
        $mapping = json_decode($mappingJson, true, 512, \JSON_THROW_ON_ERROR);
        if (is_array($mapping)) {
            return $mapping;
        }

        throw new \InvalidArgumentException('Mapping must be a JSON object.');
    }
}
