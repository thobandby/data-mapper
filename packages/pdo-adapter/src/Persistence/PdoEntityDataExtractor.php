<?php

declare(strict_types=1);

namespace DynamicDataImporter\Pdo\Persistence;

use DynamicDataImporter\Domain\Model\Row;

final class PdoEntityDataExtractor
{
    public function __construct(
        private readonly PdoAccessorColumnResolver $accessorColumnResolver = new PdoAccessorColumnResolver(),
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function extract(object $entity): array
    {
        if ($entity instanceof Row) {
            return $entity->data;
        }

        $data = [];

        foreach ((new \ReflectionObject($entity))->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
            $column = $this->accessorColumnResolver->resolve($method);
            if ($column === null) {
                continue;
            }

            $data[$column] = $method->invoke($entity);
        }

        return array_filter(
            $data,
            static fn (mixed $value, string $column): bool => ! ($column === 'id' && $value === null),
            \ARRAY_FILTER_USE_BOTH,
        );
    }
}
