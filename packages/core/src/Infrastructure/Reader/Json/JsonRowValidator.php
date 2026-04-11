<?php

declare(strict_types=1);

namespace DynamicDataImporter\Infrastructure\Reader\Json;

use DynamicDataImporter\Domain\Exception\ImporterException;
use DynamicDataImporter\Domain\Model\Row;

/**
 * @phpstan-import-type RowData from Row
 */
final class JsonRowValidator
{
    /**
     * @param list<mixed> $decoded
     *
     * @return list<RowData>
     */
    public function validate(array $decoded): array
    {
        foreach ($decoded as $rowData) {
            if (! is_array($rowData) || ($rowData !== [] && array_is_list($rowData))) {
                throw ImporterException::invalidJsonRowShape();
            }
        }

        return $decoded;
    }
}
