<?php

declare(strict_types=1);

namespace App\Tests;

use App\Kernel;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class ApiImportControllerTest extends TestCase
{
    private Kernel $kernel;

    /** @var list<string> */
    private array $cleanupFiles = [];

    /** @var list<string> */
    private array $cleanupDirectories = [];

    protected function setUp(): void
    {
        putenv('DATABASE_URL=sqlite:///:memory:');
        $_ENV['DATABASE_URL'] = 'sqlite:///:memory:';
        $_SERVER['DATABASE_URL'] = 'sqlite:///:memory:';

        $this->kernel = new Kernel('test', true);
        $this->cleanupDirectories[] = sys_get_temp_dir() . '/dynamic-data-importer/jobs';
        $this->cleanupDirectories[] = sys_get_temp_dir() . '/dynamic-data-importer/messenger/async_imports';
        $this->purgeDirectories();
    }

    protected function tearDown(): void
    {
        foreach ($this->cleanupFiles as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }

        $this->purgeDirectories();
        $this->kernel->shutdown();
    }

    public function testStartQueuesImportJobAndStatusEndpointReturnsQueuedJob(): void
    {
        $response = $this->request('POST', '/api/imports', [
            'file_type' => 'csv',
            'adapter' => 'symfony',
            'table_name' => 'imported_rows',
        ], [
            'file' => $this->createUploadedFile(),
        ]);

        self::assertSame(Response::HTTP_ACCEPTED, $response->getStatusCode());
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('queued', $payload['status']);
        self::assertStringStartsWith('/api/imports/', $payload['status_url']);

        $statusResponse = $this->request('GET', $payload['status_url']);
        self::assertSame(Response::HTTP_OK, $statusResponse->getStatusCode());
        $statusPayload = json_decode((string) $statusResponse->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame($payload['job_id'], $statusPayload['id']);
        self::assertSame('queued', $statusPayload['status']);
        self::assertSame('symfony', $statusPayload['adapter']);
    }

    public function testDocsJsonExposesImportStatusEndpoint(): void
    {
        $response = $this->request('GET', '/api/docs.json');

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertArrayHasKey('/api/imports', $payload['paths']);
        self::assertArrayHasKey('/api/imports/{jobId}', $payload['paths']);
        self::assertStringContainsString('pdo', (string) $payload['paths']['/api/imports']['post']['description']);
    }

    public function testStartRejectsMissingFile(): void
    {
        $response = $this->request('POST', '/api/imports', [
            'file_type' => 'csv',
        ]);

        self::assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        self::assertSame(
            ['error' => 'Bitte lade eine Datei hoch.'],
            json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR),
        );
    }

    public function testStartRejectsUnsupportedAdapter(): void
    {
        $response = $this->request('POST', '/api/imports', [
            'file_type' => 'csv',
            'adapter' => 'bogus',
        ], [
            'file' => $this->createUploadedFile(),
        ]);

        self::assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        self::assertSame(
            ['error' => 'Nicht unterstützter Adapter: bogus'],
            json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR),
        );
    }

    public function testStartRejectsUnsupportedFileType(): void
    {
        $response = $this->request('POST', '/api/imports', [
            'file_type' => 'yaml',
            'adapter' => 'symfony',
        ], [
            'file' => $this->createUploadedFile(),
        ]);

        self::assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        self::assertSame(
            ['error' => 'Nicht unterstützter Dateityp: yaml'],
            json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR),
        );
    }

    public function testStartRejectsInvalidMappingJson(): void
    {
        $response = $this->request('POST', '/api/imports', [
            'file_type' => 'csv',
            'adapter' => 'symfony',
            'mapping' => '{invalid}',
        ], [
            'file' => $this->createUploadedFile(),
        ]);

        self::assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        self::assertSame(
            ['error' => 'Das Mapping muss valides JSON sein.'],
            json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR),
        );
    }

    public function testStartRejectsMappingJsonWithNonStringValues(): void
    {
        $response = $this->request('POST', '/api/imports', [
            'file_type' => 'csv',
            'adapter' => 'symfony',
            'mapping' => '{"name":1}',
        ], [
            'file' => $this->createUploadedFile(),
        ]);

        self::assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        self::assertSame(
            ['error' => 'Das Mapping muss valides JSON sein.'],
            json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR),
        );
    }

    public function testStatusReturnsNotFoundForUnknownJob(): void
    {
        $response = $this->request('GET', '/api/imports/unknown-job');

        self::assertSame(Response::HTTP_NOT_FOUND, $response->getStatusCode());
        self::assertSame(
            ['error' => 'Import-Job wurde nicht gefunden.'],
            json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR),
        );
    }

    /**
     * @param array<string, string>       $parameters
     * @param array<string, UploadedFile> $files
     */
    private function request(string $method, string $uri, array $parameters = [], array $files = []): Response
    {
        $request = Request::create($uri, $method, $parameters, [], $files, [
            'HTTP_HOST' => 'localhost',
        ]);

        $response = $this->kernel->handle($request);
        $this->kernel->terminate($request, $response);

        return $response;
    }

    private function createUploadedFile(): UploadedFile
    {
        $source = __DIR__ . '/../data/sample.csv';
        $target = tempnam(sys_get_temp_dir(), 'api_import_');
        self::assertNotFalse($target);
        copy($source, $target);
        $this->cleanupFiles[] = $target;

        return new UploadedFile($target, 'sample.csv', 'text/csv', null, true);
    }

    private function purgeDirectories(): void
    {
        foreach ($this->cleanupDirectories as $directory) {
            if (! is_dir($directory)) {
                continue;
            }

            foreach (glob($directory . '/*') ?: [] as $file) {
                unlink($file);
            }

            rmdir($directory);
        }
    }
}
