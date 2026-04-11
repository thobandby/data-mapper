<?php

declare(strict_types=1);

namespace DynamicDataImporter\Domain\Transformer;

use DynamicDataImporter\Domain\Model\Row;

final readonly class ChainTransformer implements TransformerInterface
{
    /** @var list<TransformerInterface> */
    private array $transformers;

    public function __construct(TransformerInterface ...$transformers)
    {
        $this->transformers = array_values($transformers);
    }

    public function transform(Row $row): Row
    {
        return array_reduce(
            $this->transformers,
            static fn (Row $currentRow, TransformerInterface $transformer) => $transformer->transform($currentRow),
            $row
        );
    }

    /**
     * @param list<string> $headers
     *
     * @return list<string>
     */
    public function transformHeaders(array $headers): array
    {
        return array_reduce(
            $this->transformers,
            static fn (array $currentHeaders, TransformerInterface $transformer) => $transformer->transformHeaders($currentHeaders),
            $headers
        );
    }
}
