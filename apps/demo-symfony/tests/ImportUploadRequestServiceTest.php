<?php

declare(strict_types=1);

namespace App\Tests;

use App\Import\Http\ResolvedUploadRequest;
use App\Import\Http\UploadRequestError;
use App\Service\ImportManager;
use App\Service\ImportUploadRequestService;
use App\Service\ImportUploadRequestValidator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Validator\Validation;

final class ImportUploadRequestServiceTest extends TestCase
{
    /** @var list<string> */
    private array $cleanupFiles = [];

    protected function tearDown(): void
    {
        foreach ($this->cleanupFiles as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
    }

    public function testResolveForApiReturnsStoredRequestWithNormalizedValues(): void
    {
        $service = $this->createService();

        $result = $service->resolveForApi(
            $this->createUploadedFile('sample.csv', 'text/csv'),
            ' JSON ',
            'auto',
            'orders-import!',
            ';',
            '{"sku":"article_number"}',
        );

        self::assertInstanceOf(ResolvedUploadRequest::class, $result);
        self::assertSame('json', $result->adapter);
        self::assertSame('csv', $result->fileType);
        self::assertSame('ordersimport', $result->tableName);
        self::assertSame(';', $result->delimiter);
        self::assertSame(['sku' => 'article_number'], $result->mapping);
        self::assertFileExists(sys_get_temp_dir() . '/' . $result->storedFile);
        $this->cleanupFiles[] = sys_get_temp_dir() . '/' . $result->storedFile;
    }

    public function testResolveForApiRejectsInvalidMappingJson(): void
    {
        $service = $this->createService();

        $result = $service->resolveForApi(
            $this->createUploadedFile('sample.csv', 'text/csv'),
            'symfony',
            'csv',
            'imported_rows',
            null,
            '{"sku":1}',
        );

        self::assertInstanceOf(UploadRequestError::class, $result);
        self::assertSame('api.error.invalid_mapping_json', $result->messageKey);
    }

    public function testResolveForWebRejectsUnsupportedAdapter(): void
    {
        $service = $this->createService();

        $result = $service->resolveForWeb(
            $this->createUploadedFile('sample.csv', 'text/csv'),
            'bogus',
            'csv',
        );

        self::assertInstanceOf(UploadRequestError::class, $result);
        self::assertSame('upload.error.unsupported_adapter', $result->messageKey);
        self::assertSame(['%adapter%' => 'bogus'], $result->parameters);
    }

    private function createUploadedFile(string $clientName, string $mimeType): UploadedFile
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'upload_request_');
        self::assertNotFalse($tempFile);
        file_put_contents($tempFile, "name\nAlice\n");
        $this->cleanupFiles[] = $tempFile;

        return new UploadedFile($tempFile, $clientName, $mimeType, null, true);
    }

    private function createService(): ImportUploadRequestService
    {
        $manager = new ImportManager();

        return new ImportUploadRequestService(
            $manager,
            new ImportUploadRequestValidator(Validation::createValidator(), $manager),
        );
    }
}
