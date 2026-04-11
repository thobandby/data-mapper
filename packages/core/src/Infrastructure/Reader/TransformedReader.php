<?php

declare(strict_types=1);

namespace DynamicDataImporter\Infrastructure\Reader;

use DynamicDataImporter\Domain\Model\Row;
use DynamicDataImporter\Domain\Transformer\TransformerInterface;
use DynamicDataImporter\Port\Reader\TabularReaderInterface;

final readonly class TransformedReader implements TabularReaderInterface
{
    public function __construct(
        private TabularReaderInterface $reader,
        private TransformerInterface $transformer,
    ) {
    }

    /**
     * @return list<string>
     */
    public function headers(): array
    {
        return $this->transformer->transformHeaders($this->reader->headers());
    }

    /**
     * @return iterable<Row>
     */
    public function rows(): iterable
    {
        foreach ($this->reader->rows() as $row) {
            yield $this->transformer->transform($row);
        }
    }
}
