<?php

declare(strict_types=1);

namespace App\Tests;

use App\Service\ImportArtifactStorage;
use App\Service\ImportExecutionService;
use App\Service\ImportResultArtifactService;
use PHPUnit\Framework\TestCase;

final class ImportResultArtifactServiceTest extends TestCase
{
    private string $artifactDirectory;
    /** @var list<string> */
    private array $cleanupFiles = [];

    protected function setUp(): void
    {
        $this->artifactDirectory = sys_get_temp_dir() . '/import-result-artifacts-' . bin2hex(random_bytes(6));
        mkdir($this->artifactDirectory, 0o777, true);
    }

    protected function tearDown(): void
    {
        foreach ($this->cleanupFiles as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }

        if (is_dir($this->artifactDirectory)) {
            rmdir($this->artifactDirectory);
        }
    }

    public function testDownloadResponseReturnsAttachmentForManagedArtifact(): void
    {
        $storage = new ImportArtifactStorage($this->artifactDirectory);
        $service = new ImportResultArtifactService($storage);
        $artifactPath = $storage->allocatePath('json');
        file_put_contents($artifactPath, '[{"name":"Alice"}]');
        $this->cleanupFiles[] = $artifactPath;

        $response = $service->downloadResponse($artifactPath, 'json');

        self::assertNotNull($response);
        self::assertSame('application/json', $response->headers->get('Content-Type'));
        self::assertStringContainsString(
            ImportExecutionService::RESULT_JSON_BASENAME,
            (string) $response->headers->get('Content-Disposition'),
        );
    }

    public function testDownloadResponseRejectsExistingFileOutsideManagedDirectory(): void
    {
        $service = new ImportResultArtifactService(new ImportArtifactStorage($this->artifactDirectory));
        $externalFile = tempnam(sys_get_temp_dir(), 'external-artifact-');
        self::assertNotFalse($externalFile);
        file_put_contents($externalFile, 'secret');
        $this->cleanupFiles[] = $externalFile;

        self::assertNull($service->downloadResponse($externalFile, 'json'));
    }

    public function testReplaceDoesNotDeleteUnmanagedExistingFile(): void
    {
        $service = new ImportResultArtifactService(new ImportArtifactStorage($this->artifactDirectory));
        $externalFile = tempnam(sys_get_temp_dir(), 'external-delete-');
        self::assertNotFalse($externalFile);
        file_put_contents($externalFile, 'keep');
        $this->cleanupFiles[] = $externalFile;

        $service->replace($externalFile, null);

        self::assertFileExists($externalFile);
    }
}
