<?php

declare(strict_types=1);

namespace DynamicDataImporter\Domain\Transformer;

use DynamicDataImporter\Domain\Model\Row;

interface TransformerInterface
{
    public function transform(Row $row): Row;

    /**
     * @param list<string> $headers
     *
     * @return list<string>
     */
    public function transformHeaders(array $headers): array;
}
