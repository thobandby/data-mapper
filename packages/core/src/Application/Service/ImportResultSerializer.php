<?php

declare(strict_types=1);

namespace DynamicDataImporter\Application\Service;

use DynamicDataImporter\Domain\Model\ImportResult;

final class ImportResultSerializer
{
    /**
     * @return array{
     *   processed: int,
     *   imported: int,
     *   errors: list<array{
     *     row_index: int,
     *     field_errors: array<string, string>,
     *     message: ?string
     *   }>
     * }
     */
    public function serialize(ImportResult $result): array
    {
        return [
            'processed' => $result->processed,
            'imported' => $result->imported,
            'errors' => array_map(
                static fn ($error): array => [
                    'row_index' => $error->rowIndex,
                    'field_errors' => $error->fieldErrors,
                    'message' => $error->message,
                ],
                $result->errors,
            ),
        ];
    }
}
