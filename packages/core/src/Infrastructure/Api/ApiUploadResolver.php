<?php

declare(strict_types=1);

namespace DynamicDataImporter\Infrastructure\Api;

final class ApiUploadResolver
{
    /**
     * @param array<string, mixed> $files
     *
     * @return array{tmp_name: string, file_type: string}
     */
    public function requireUpload(array $files): array
    {
        $file = $files['file'] ?? null;
        if (! is_array($file)) {
            throw new \InvalidArgumentException('No file uploaded.');
        }

        $tmpName = (string) ($file['tmp_name'] ?? '');
        if ($tmpName === '' || ! is_file($tmpName)) {
            throw new \InvalidArgumentException('Uploaded file is invalid.');
        }

        return [
            'tmp_name' => $tmpName,
            'file_type' => strtolower(pathinfo((string) ($file['name'] ?? ''), \PATHINFO_EXTENSION)),
        ];
    }
}
