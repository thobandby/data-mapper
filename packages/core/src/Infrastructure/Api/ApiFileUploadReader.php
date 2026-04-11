<?php

declare(strict_types=1);

namespace DynamicDataImporter\Infrastructure\Api;

final class ApiFileUploadReader
{
    /**
     * @return array<string, mixed>
     */
    public function read(): array
    {
        return $_FILES;
    }
}
