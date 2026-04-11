<?php

declare(strict_types=1);

namespace DynamicDataImporter\Domain\Transformer\Mapping;

use DynamicDataImporter\Domain\Exception\ImporterException;
use DynamicDataImporter\Domain\Model\Row;
use DynamicDataImporter\Domain\Transformer\TransformerInterface;

final readonly class MappingTransformer implements TransformerInterface
{
    /** @param array<string, string> $mapping [original_header => new_header] */
    public function __construct(
        private array $mapping,
    ) {
    }

    public function transform(Row $row): Row
    {
        $newData = [];
        foreach ($row->data as $key => $value) {
            $newKey = $this->mapping[$key] ?? $key;
            if ($newKey === '') {
                continue;
            }

            if (array_key_exists($newKey, $newData)) {
                throw ImporterException::mappingCollision($newKey);
            }
            $newData[$newKey] = $value;
        }

        return new Row($row->index, $newData);
    }

    /**
     * @param list<string> $headers
     *
     * @return list<string>
     */
    public function transformHeaders(array $headers): array
    {
        $transformedHeaders = array_values(array_filter(
            array_map(
                fn (string $header) => $this->mapping[$header] ?? $header,
                $headers
            ),
            static fn (string $header): bool => $header !== '',
        ));

        if (count($transformedHeaders) !== count(array_unique($transformedHeaders))) {
            throw ImporterException::mappingHeaderCollision();
        }

        return $transformedHeaders;
    }
}
