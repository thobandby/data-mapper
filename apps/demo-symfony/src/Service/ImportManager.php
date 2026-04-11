<?php

declare(strict_types=1);

namespace App\Service;

use DynamicDataImporter\Domain\Exception\ImporterException;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class ImportManager
{
    private const int MAX_UPLOAD_BYTES = 10_485_760;

    private string $tempDir;

    public function __construct()
    {
        $this->tempDir = sys_get_temp_dir();
    }

    public function storeUploadedFile(UploadedFile $file): string
    {
        $filename = sprintf(
            'import_%s.%s',
            bin2hex(random_bytes(16)),
            $file->getClientOriginalExtension()
        );
        $file->move($this->tempDir, $filename);

        return $filename;
    }

    public function getFilePath(string $filename): string
    {
        return $this->tempDir . '/' . basename($filename);
    }

    public function getExistingFilePath(string $filename): string
    {
        $filePath = $this->getFilePath($filename);

        if (! is_file($filePath)) {
            throw ImporterException::fileNotFound($filePath);
        }

        return $filePath;
    }

    public function getEffectiveFileType(UploadedFile $file, string $requestedType = 'auto'): string
    {
        if ($requestedType === 'auto') {
            return $file->getClientOriginalExtension();
        }

        return $requestedType;
    }

    public function validateFileType(string $fileType): bool
    {
        $allowedTypes = ['csv', 'xlsx', 'xls', 'json', 'xml'];

        return in_array(strtolower($fileType), $allowedTypes, true);
    }

    public function maxUploadBytes(): int
    {
        return self::MAX_UPLOAD_BYTES;
    }

    public function maxUploadMegabytes(): int
    {
        return (int) ceil(self::MAX_UPLOAD_BYTES / 1024 / 1024);
    }
}
