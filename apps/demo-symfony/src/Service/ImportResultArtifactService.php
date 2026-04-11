<?php

declare(strict_types=1);

namespace App\Service;

use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

final class ImportResultArtifactService
{
    public function __construct(
        private readonly ImportArtifactStorage $importArtifactStorage,
    ) {
    }

    public function replace(?string $currentPath, ?string $nextPath): void
    {
        if ($currentPath !== '' && $currentPath !== $nextPath && is_file($currentPath)) {
            $this->importArtifactStorage->deleteIfExists($currentPath);
        }
    }

    public function downloadResponse(string $filePath, string $format): ?BinaryFileResponse
    {
        if (! $this->importArtifactStorage->isManagedPath($filePath) || ! is_file($filePath)) {
            return null;
        }

        $response = new BinaryFileResponse($filePath);
        $response->headers->set('Content-Type', $this->downloadContentType($format));
        $response->setContentDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            $this->downloadFilename($format),
        );

        return $response;
    }

    private function downloadFilename(string $format): string
    {
        return match ($format) {
            'json' => ImportExecutionService::RESULT_JSON_BASENAME,
            'xml' => ImportExecutionService::RESULT_XML_BASENAME,
            'sql' => ImportExecutionService::RESULT_SQL_BASENAME,
            default => ImportExecutionService::RESULT_JSON_BASENAME,
        };
    }

    private function downloadContentType(string $format): string
    {
        return match ($format) {
            'xml' => 'application/xml',
            'sql' => 'application/sql',
            default => 'application/json',
        };
    }
}
