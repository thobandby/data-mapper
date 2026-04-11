<?php

declare(strict_types=1);

namespace DynamicDataImporter\Infrastructure\Reader\Json;

use DynamicDataImporter\Domain\Exception\ImporterException;
use DynamicDataImporter\Domain\Model\Row;

/**
 * @phpstan-import-type RowData from Row
 */
final class JsonRowDecoder
{
    private readonly JsonRowValidator $validator;

    public function __construct()
    {
        $this->validator = new JsonRowValidator();
    }

    /**
     * @return list<RowData>
     */
    public function decode(string $content): array
    {
        $decoded = json_decode($content, true);
        if (json_last_error() !== \JSON_ERROR_NONE) {
            throw ImporterException::invalidJson(json_last_error_msg());
        }

        if (! is_array($decoded)) {
            throw ImporterException::invalidJsonRoot();
        }

        return $this->validator->validate($decoded);
    }
}
