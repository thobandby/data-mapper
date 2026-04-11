<?php

declare(strict_types=1);

namespace App\Tests;

use App\Kernel;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

final class ImportWizardFlowTest extends TestCase
{
    private const SESSION_JSON_RESULT_FILE = 'import.result_file';

    private Kernel $kernel;
    private Session $session;

    /** @var list<string> */
    private array $cleanupFiles = [];

    protected function setUp(): void
    {
        putenv('DATABASE_URL=sqlite:///:memory:');
        $_ENV['DATABASE_URL'] = 'sqlite:///:memory:';
        $_SERVER['DATABASE_URL'] = 'sqlite:///:memory:';

        $this->kernel = new Kernel('test', true);
        $this->session = new Session(new MockArraySessionStorage());
        $this->session->start();
    }

    protected function tearDown(): void
    {
        foreach ($this->cleanupFiles as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }

        if (isset($this->kernel)) {
            $this->kernel->shutdown();
        }
    }

    public function testTurboWizardFlowRendersAndProcessesImport(): void
    {
        $indexResponse = $this->request('GET', '/');
        $indexHtml = $this->responseContent($indexResponse);
        self::assertSame(Response::HTTP_OK, $indexResponse->getStatusCode());
        self::assertStringContainsString('<turbo-frame id="import_step">', $indexHtml);
        self::assertStringContainsString('Quelldatei und Zielmodus wählen', $indexHtml);

        $uploadToken = $this->extractTokenForForm($indexHtml, '/');
        $uploadedFile = $this->createUploadedFileCopy();

        $uploadResponse = $this->request('POST', '/', [
            '_token' => $uploadToken,
            'file_type' => 'csv',
            'adapter' => 'memory',
        ], [
            'file' => $uploadedFile,
        ], [
            'HTTP_TURBO_FRAME' => 'import_step',
        ]);

        self::assertSame(Response::HTTP_FOUND, $uploadResponse->getStatusCode());
        $schemaLocation = (string) $uploadResponse->headers->get('Location');
        parse_str((string) parse_url($schemaLocation, PHP_URL_QUERY), $schemaQuery);
        if (isset($schemaQuery['file']) && is_string($schemaQuery['file'])) {
            $this->cleanupFiles[] = sys_get_temp_dir() . '/' . basename($schemaQuery['file']);
        }

        $schemaResponse = $this->followRedirect($uploadResponse, [
            'HTTP_TURBO_FRAME' => 'import_step',
        ]);
        $schemaHtml = $this->responseContent($schemaResponse);
        self::assertSame(Response::HTTP_OK, $schemaResponse->getStatusCode());
        self::assertStringContainsString('Zielschema und Struktur prüfen', $schemaHtml);
        self::assertStringContainsString('Aktiv', $schemaHtml);

        $schemaToken = $this->extractTokenForForm($schemaHtml, '/import/schema');
        $schemaResponsePost = $this->request('POST', '/import/schema', [
            '_token' => $schemaToken,
            'file' => (string) ($schemaQuery['file'] ?? ''),
            'file_type' => 'csv',
            'adapter' => 'memory',
            'table' => 'imported_rows',
        ], [], [
            'HTTP_TURBO_FRAME' => 'import_step',
        ]);

        self::assertSame(Response::HTTP_FOUND, $schemaResponsePost->getStatusCode());

        $mappingResponse = $this->followRedirect($schemaResponsePost, [
            'HTTP_TURBO_FRAME' => 'import_step',
        ]);
        $mappingHtml = $this->responseContent($mappingResponse);
        self::assertSame(Response::HTTP_OK, $mappingResponse->getStatusCode());
        self::assertStringContainsString('Felder zuordnen und Ziel prüfen', $mappingHtml);
        self::assertStringContainsString('Resultierende Vorschau', $mappingHtml);
        self::assertStringContainsString('class="mapping-board"', $mappingHtml);
        self::assertStringContainsString('class="mapping-box is-source"', $mappingHtml);
        self::assertStringContainsString('class="mapping-box is-target mapping-target-control"', $mappingHtml);

        $processToken = $this->extractTokenForForm($mappingHtml, '/import/process');
        $processForm = $this->extractHiddenFieldsForForm($mappingHtml, '/import/process');

        $processResponse = $this->request('POST', '/import/process', array_merge($processForm, [
            '_token' => $processToken,
        ]), [], [
            'HTTP_TURBO_FRAME' => 'import_step',
        ]);

        $resultHtml = $this->responseContent($processResponse);
        self::assertSame(Response::HTTP_OK, $processResponse->getStatusCode());
        self::assertStringContainsString('Laufstatus und Ergebnis', $resultHtml);
        self::assertStringContainsString('Verarbeitet', $resultHtml);
    }

    public function testWizardCanRenderEnglishTranslations(): void
    {
        $response = $this->request('GET', '/?locale=en');
        $html = $this->responseContent($response);

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertStringContainsString('Choose source file and target mode', $html);
        self::assertStringContainsString('File import as a technical workflow', $html);
        self::assertStringContainsString('Continue to preview', $html);
    }

    public function testTurboWizardJsonResultEscapesFrameForDownload(): void
    {
        $indexResponse = $this->request('GET', '/');
        self::assertSame(Response::HTTP_OK, $indexResponse->getStatusCode());
        $indexHtml = $this->responseContent($indexResponse);

        $uploadToken = $this->extractTokenForForm($indexHtml, '/');
        $uploadedFile = $this->createUploadedFileCopy();

        $uploadResponse = $this->request('POST', '/', [
            '_token' => $uploadToken,
            'file_type' => 'csv',
            'adapter' => 'json',
        ], [
            'file' => $uploadedFile,
        ], [
            'HTTP_TURBO_FRAME' => 'import_step',
        ]);

        self::assertSame(Response::HTTP_FOUND, $uploadResponse->getStatusCode());
        $schemaLocation = (string) $uploadResponse->headers->get('Location');
        parse_str((string) parse_url($schemaLocation, PHP_URL_QUERY), $schemaQuery);
        if (isset($schemaQuery['file']) && is_string($schemaQuery['file'])) {
            $this->cleanupFiles[] = sys_get_temp_dir() . '/' . basename($schemaQuery['file']);
        }

        $schemaResponse = $this->followRedirect($uploadResponse, [
            'HTTP_TURBO_FRAME' => 'import_step',
        ]);
        $schemaToken = $this->extractTokenForForm($this->responseContent($schemaResponse), '/import/schema');

        $schemaResponsePost = $this->request('POST', '/import/schema', [
            '_token' => $schemaToken,
            'file' => (string) ($schemaQuery['file'] ?? ''),
            'file_type' => 'csv',
            'adapter' => 'json',
            'table' => 'imported_rows',
        ], [], [
            'HTTP_TURBO_FRAME' => 'import_step',
        ]);

        self::assertSame(Response::HTTP_FOUND, $schemaResponsePost->getStatusCode());

        $mappingResponse = $this->followRedirect($schemaResponsePost, [
            'HTTP_TURBO_FRAME' => 'import_step',
        ]);
        $mappingHtml = $this->responseContent($mappingResponse);

        $processToken = $this->extractTokenForForm($mappingHtml, '/import/process');
        $processForm = $this->extractHiddenFieldsForForm($mappingHtml, '/import/process');

        $processResponse = $this->request('POST', '/import/process', array_merge($processForm, [
            '_token' => $processToken,
        ]), [], [
            'HTTP_TURBO_FRAME' => 'import_step',
        ]);

        $resultHtml = $this->responseContent($processResponse);
        self::assertSame(Response::HTTP_OK, $processResponse->getStatusCode());
        self::assertStringContainsString('JSON-Artefakt steht bereit', $resultHtml);
        self::assertStringContainsString('href="/import/download"', $resultHtml);
        self::assertStringContainsString('data-turbo-frame="_top"', $resultHtml);
        self::assertStringContainsString('target="_top"', $resultHtml);
    }

    public function testTurboWizardSqlResultEscapesFrameForDownload(): void
    {
        $indexResponse = $this->request('GET', '/');
        self::assertSame(Response::HTTP_OK, $indexResponse->getStatusCode());
        $indexHtml = $this->responseContent($indexResponse);

        $uploadToken = $this->extractTokenForForm($indexHtml, '/');
        $uploadedFile = $this->createUploadedFileCopy();

        $uploadResponse = $this->request('POST', '/', [
            '_token' => $uploadToken,
            'file_type' => 'csv',
            'adapter' => 'sql',
        ], [
            'file' => $uploadedFile,
        ], [
            'HTTP_TURBO_FRAME' => 'import_step',
        ]);

        self::assertSame(Response::HTTP_FOUND, $uploadResponse->getStatusCode());
        $schemaLocation = (string) $uploadResponse->headers->get('Location');
        parse_str((string) parse_url($schemaLocation, PHP_URL_QUERY), $schemaQuery);
        if (isset($schemaQuery['file']) && is_string($schemaQuery['file'])) {
            $this->cleanupFiles[] = sys_get_temp_dir() . '/' . basename($schemaQuery['file']);
        }

        $schemaResponse = $this->followRedirect($uploadResponse, [
            'HTTP_TURBO_FRAME' => 'import_step',
        ]);
        $schemaToken = $this->extractTokenForForm($this->responseContent($schemaResponse), '/import/schema');

        $schemaResponsePost = $this->request('POST', '/import/schema', [
            '_token' => $schemaToken,
            'file' => (string) ($schemaQuery['file'] ?? ''),
            'file_type' => 'csv',
            'adapter' => 'sql',
            'table' => 'imported_rows',
        ], [], [
            'HTTP_TURBO_FRAME' => 'import_step',
        ]);

        self::assertSame(Response::HTTP_FOUND, $schemaResponsePost->getStatusCode());

        $mappingResponse = $this->followRedirect($schemaResponsePost, [
            'HTTP_TURBO_FRAME' => 'import_step',
        ]);
        $mappingHtml = $this->responseContent($mappingResponse);

        $processToken = $this->extractTokenForForm($mappingHtml, '/import/process');
        $processForm = $this->extractHiddenFieldsForForm($mappingHtml, '/import/process');

        $processResponse = $this->request('POST', '/import/process', array_merge($processForm, [
            '_token' => $processToken,
        ]), [], [
            'HTTP_TURBO_FRAME' => 'import_step',
        ]);

        $resultHtml = $this->responseContent($processResponse);
        self::assertSame(Response::HTTP_OK, $processResponse->getStatusCode());
        self::assertStringContainsString('SQL-Artefakt steht bereit', $resultHtml);
        self::assertStringContainsString('href="/import/download"', $resultHtml);
        self::assertStringContainsString('data-turbo-frame="_top"', $resultHtml);
        self::assertStringContainsString('target="_top"', $resultHtml);
    }

    public function testSetupDbDispatchRedirectsBackToMappingWithSuccessMessage(): void
    {
        $mappingResponse = $this->arriveAtMappingStep('csv', 'symfony');
        $mappingHtml = $this->responseContent($mappingResponse);
        $setupToken = $this->extractTokenForForm($mappingHtml, '/import/setup-db');
        $setupForm = $this->extractHiddenFieldsForForm($mappingHtml, '/import/setup-db');

        $setupResponse = $this->request('POST', '/import/setup-db', array_merge($setupForm, [
            '_token' => $setupToken,
        ]), [], [
            'HTTP_TURBO_FRAME' => 'import_step',
        ]);

        self::assertSame(Response::HTTP_FOUND, $setupResponse->getStatusCode());
        $location = (string) $setupResponse->headers->get('Location');
        self::assertStringContainsString('/import/mapping', $location);
        self::assertStringContainsString('table=imported_rows', $location);

        $redirectResponse = $this->followRedirect($setupResponse, [
            'HTTP_TURBO_FRAME' => 'import_step',
        ]);
        $redirectHtml = $this->responseContent($redirectResponse);

        self::assertSame(Response::HTTP_OK, $redirectResponse->getStatusCode());
        self::assertStringContainsString('Datenbank-Setup wurde eingeplant. Tabelle &quot;imported_rows&quot; wird im Hintergrund erstellt.', $redirectHtml);
    }

    public function testSymfonySchemaStepRendersSourceToTableColumnBoardForNewTables(): void
    {
        $indexResponse = $this->request('GET', '/');
        $indexHtml = $this->responseContent($indexResponse);

        $uploadToken = $this->extractTokenForForm($indexHtml, '/');
        $uploadedFile = $this->createUploadedFileCopy();

        $uploadResponse = $this->request('POST', '/', [
            '_token' => $uploadToken,
            'file_type' => 'csv',
            'adapter' => 'symfony',
        ], [
            'file' => $uploadedFile,
        ], [
            'HTTP_TURBO_FRAME' => 'import_step',
        ]);

        self::assertSame(Response::HTTP_FOUND, $uploadResponse->getStatusCode());

        $schemaResponse = $this->followRedirect($uploadResponse, [
            'HTTP_TURBO_FRAME' => 'import_step',
        ]);
        $schemaHtml = $this->responseContent($schemaResponse);

        self::assertSame(Response::HTTP_OK, $schemaResponse->getStatusCode());
        self::assertStringContainsString('class="schema-column-board"', $schemaHtml);
        self::assertStringContainsString('class="schema-column-row"', $schemaHtml);
        self::assertStringContainsString('class="mapping-box is-source"', $schemaHtml);
        self::assertStringContainsString('class="mapping-box is-target mapping-target-control"', $schemaHtml);
        self::assertStringContainsString('name="source_headers[0]" value="name"', $schemaHtml);
        self::assertStringContainsString('name="columns[0]"', $schemaHtml);
    }

    public function testDownloadRedirectsWhenJsonSessionArtifactIsMissing(): void
    {
        $response = $this->request('GET', '/import/download');

        self::assertSame(Response::HTTP_FOUND, $response->getStatusCode());
        self::assertSame('/', $response->headers->get('Location'));

        $redirectResponse = $this->followRedirect($response, [
            'HTTP_TURBO_FRAME' => 'import_step',
        ]);
        $redirectHtml = $this->responseContent($redirectResponse);

        self::assertStringContainsString('Download-Datei wurde nicht gefunden.', $redirectHtml);
    }

    public function testDownloadReturnsJsonAttachmentWhenArtifactExists(): void
    {
        $resultResponse = $this->completeProcessStep('csv', 'json');
        self::assertSame(Response::HTTP_OK, $resultResponse->getStatusCode());

        $downloadResponse = $this->request('GET', '/import/download');

        self::assertSame(Response::HTTP_OK, $downloadResponse->getStatusCode());
        self::assertSame('application/json', $downloadResponse->headers->get('Content-Type'));
        self::assertStringContainsString('attachment;', (string) $downloadResponse->headers->get('Content-Disposition'));
        self::assertStringContainsString('import_result.json', (string) $downloadResponse->headers->get('Content-Disposition'));
    }

    public function testDownloadReturnsXmlAttachmentWhenArtifactExists(): void
    {
        $resultResponse = $this->completeProcessStep('csv', 'xml');
        self::assertSame(Response::HTTP_OK, $resultResponse->getStatusCode());

        $downloadResponse = $this->request('GET', '/import/download');

        self::assertSame(Response::HTTP_OK, $downloadResponse->getStatusCode());
        self::assertSame('application/xml', $downloadResponse->headers->get('Content-Type'));
        self::assertStringContainsString('attachment;', (string) $downloadResponse->headers->get('Content-Disposition'));
        self::assertStringContainsString('import_result.xml', (string) $downloadResponse->headers->get('Content-Disposition'));
    }

    public function testDownloadReturnsSqlAttachmentWhenArtifactExists(): void
    {
        $resultResponse = $this->completeProcessStep('csv', 'sql');
        self::assertSame(Response::HTTP_OK, $resultResponse->getStatusCode());

        $downloadResponse = $this->request('GET', '/import/download');

        self::assertSame(Response::HTTP_OK, $downloadResponse->getStatusCode());
        self::assertSame('application/sql', $downloadResponse->headers->get('Content-Type'));
        self::assertStringContainsString('attachment;', (string) $downloadResponse->headers->get('Content-Disposition'));
        self::assertStringContainsString('import_result.sql', (string) $downloadResponse->headers->get('Content-Disposition'));
    }

    public function testProcessRejectsInvalidCsrfAndRedirectsBackToMapping(): void
    {
        $mappingResponse = $this->arriveAtMappingStep('csv', 'memory');
        $mappingHtml = $this->responseContent($mappingResponse);
        $processForm = $this->extractHiddenFieldsForForm($mappingHtml, '/import/process');

        $processResponse = $this->request('POST', '/import/process', array_merge($processForm, [
            '_token' => 'invalid-token',
        ]), [], [
            'HTTP_TURBO_FRAME' => 'import_step',
        ]);

        self::assertSame(Response::HTTP_FOUND, $processResponse->getStatusCode());
        self::assertStringContainsString('/import/mapping', (string) $processResponse->headers->get('Location'));

        $redirectResponse = $this->followRedirect($processResponse, [
            'HTTP_TURBO_FRAME' => 'import_step',
        ]);
        self::assertStringContainsString('Ungültiges CSRF-Token.', $this->responseContent($redirectResponse));
    }

    public function testProcessReplacesPreviousJsonArtifactAndClearsItAfterNonJsonRun(): void
    {
        $firstJsonResponse = $this->completeProcessStep('csv', 'json');
        self::assertSame(Response::HTTP_OK, $firstJsonResponse->getStatusCode());

        $firstArtifact = (string) $this->session->get(self::SESSION_JSON_RESULT_FILE, '');
        self::assertNotSame('', $firstArtifact);
        self::assertFileExists($firstArtifact);

        $secondJsonResponse = $this->completeProcessStep('csv', 'json');
        self::assertSame(Response::HTTP_OK, $secondJsonResponse->getStatusCode());

        $secondArtifact = (string) $this->session->get(self::SESSION_JSON_RESULT_FILE, '');
        self::assertNotSame('', $secondArtifact);
        self::assertNotSame($firstArtifact, $secondArtifact);
        self::assertFileDoesNotExist($firstArtifact);
        self::assertFileExists($secondArtifact);

        $memoryResponse = $this->completeProcessStep('csv', 'memory');
        self::assertSame(Response::HTTP_OK, $memoryResponse->getStatusCode());
        self::assertNull($this->session->get(self::SESSION_JSON_RESULT_FILE));
        self::assertFileDoesNotExist($secondArtifact);
    }

    public function testTurboWizardFlowProcessesSpreadsheetImport(): void
    {
        $indexResponse = $this->request('GET', '/');
        $indexHtml = $this->responseContent($indexResponse);
        self::assertSame(Response::HTTP_OK, $indexResponse->getStatusCode());

        $uploadToken = $this->extractTokenForForm($indexHtml, '/');
        $uploadedFile = $this->createUploadedFileCopy(
            __DIR__ . '/../../../packages/core/tests/Infrastructure/Reader/Spreadsheet/data/sample.xlsx',
            'sample.xlsx',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        );

        $uploadResponse = $this->request('POST', '/', [
            '_token' => $uploadToken,
            'file_type' => 'xlsx',
            'adapter' => 'memory',
        ], [
            'file' => $uploadedFile,
        ], [
            'HTTP_TURBO_FRAME' => 'import_step',
        ]);

        self::assertSame(Response::HTTP_FOUND, $uploadResponse->getStatusCode());

        $schemaResponse = $this->followRedirect($uploadResponse, [
            'HTTP_TURBO_FRAME' => 'import_step',
        ]);
        $schemaHtml = $this->responseContent($schemaResponse);
        self::assertStringContainsString('name', $schemaHtml);
        self::assertStringContainsString('alice@example.com', $schemaHtml);

        $schemaToken = $this->extractTokenForForm($schemaHtml, '/import/schema');
        $schemaForm = $this->extractHiddenFieldsForForm($schemaHtml, '/import/schema');

        $schemaResponsePost = $this->request('POST', '/import/schema', array_merge($schemaForm, [
            '_token' => $schemaToken,
            'table' => 'imported_rows',
        ]), [], [
            'HTTP_TURBO_FRAME' => 'import_step',
        ]);

        $mappingResponse = $this->followRedirect($schemaResponsePost, [
            'HTTP_TURBO_FRAME' => 'import_step',
        ]);
        $mappingHtml = $this->responseContent($mappingResponse);
        self::assertStringContainsString('Resultierende Vorschau', $mappingHtml);
        self::assertStringContainsString('alice@example.com', $mappingHtml);

        $processToken = $this->extractTokenForForm($mappingHtml, '/import/process');
        $processForm = $this->extractHiddenFieldsForForm($mappingHtml, '/import/process');
        $processResponse = $this->request('POST', '/import/process', array_merge($processForm, [
            '_token' => $processToken,
        ]), [], [
            'HTTP_TURBO_FRAME' => 'import_step',
        ]);

        $resultHtml = $this->responseContent($processResponse);
        self::assertSame(Response::HTTP_OK, $processResponse->getStatusCode());
        self::assertStringContainsString('Laufstatus und Ergebnis', $resultHtml);
        self::assertStringContainsString('Verarbeitet', $resultHtml);
    }

    public function testSchemaSelectionCanIgnoreColumnsBeforeMapping(): void
    {
        $indexResponse = $this->request('GET', '/');
        $uploadToken = $this->extractTokenForForm($this->responseContent($indexResponse), '/');

        $uploadResponse = $this->request('POST', '/', [
            '_token' => $uploadToken,
            'file_type' => 'csv',
            'adapter' => 'symfony',
        ], [
            'file' => $this->createUploadedFileCopy(),
        ], [
            'HTTP_TURBO_FRAME' => 'import_step',
        ]);

        $schemaLocation = (string) $uploadResponse->headers->get('Location');
        parse_str((string) parse_url($schemaLocation, PHP_URL_QUERY), $schemaQuery);
        if (isset($schemaQuery['file']) && is_string($schemaQuery['file'])) {
            $this->cleanupFiles[] = sys_get_temp_dir() . '/' . basename($schemaQuery['file']);
        }

        $schemaResponse = $this->followRedirect($uploadResponse, [
            'HTTP_TURBO_FRAME' => 'import_step',
        ]);
        $schemaHtml = $this->responseContent($schemaResponse);
        $schemaToken = $this->extractTokenForForm($schemaHtml, '/import/schema');
        $schemaForm = $this->extractHiddenFieldsForForm($schemaHtml, '/import/schema');

        $schemaPostResponse = $this->request('POST', '/import/schema', array_merge($schemaForm, [
            '_token' => $schemaToken,
            'table' => 'imported_rows',
            'source_headers' => ['name', 'age', 'email'],
            'columns' => ['full_name', 'age', 'email'],
            'selected_columns' => ['0', '2'],
        ]), [], [
            'HTTP_TURBO_FRAME' => 'import_step',
        ]);

        self::assertSame(Response::HTTP_FOUND, $schemaPostResponse->getStatusCode());

        $mappingResponse = $this->followRedirect($schemaPostResponse, [
            'HTTP_TURBO_FRAME' => 'import_step',
        ]);
        $mappingHtml = $this->responseContent($mappingResponse);

        self::assertStringContainsString('<th>full_name</th>', $mappingHtml);
        self::assertStringContainsString('<th>email</th>', $mappingHtml);
        self::assertStringNotContainsString('<th>age</th>', $mappingHtml);
    }

    public function testSchemaStepShowsFriendlyMessageForCorruptedCsv(): void
    {
        $indexResponse = $this->request('GET', '/');
        $uploadToken = $this->extractTokenForForm($this->responseContent($indexResponse), '/');

        $uploadResponse = $this->request('POST', '/', [
            '_token' => $uploadToken,
            'file_type' => 'csv',
            'adapter' => 'symfony',
        ], [
            'file' => $this->createUploadedFileCopy(
                __DIR__ . '/../../../packages/core/tests/data/csv_invalid_unescaped_semicolon.csv',
                'csv_invalid_unescaped_semicolon.csv',
                'text/csv',
            ),
        ], [
            'HTTP_TURBO_FRAME' => 'import_step',
        ]);

        $schemaResponse = $this->followRedirect($uploadResponse, [
            'HTTP_TURBO_FRAME' => 'import_step',
        ]);
        $schemaHtml = $this->responseContent($schemaResponse);

        self::assertStringContainsString('Die CSV-Datei ist in Zeile 18 beschädigt: erwartet wurden 5 Spalten, gefunden wurden 6.', $schemaHtml);
        self::assertStringNotContainsString('Invalid CSV:', $schemaHtml);
    }

    public function testSchemaStepShowsFriendlyMessageForCorruptedJson(): void
    {
        $indexResponse = $this->request('GET', '/');
        $uploadToken = $this->extractTokenForForm($this->responseContent($indexResponse), '/');

        $uploadResponse = $this->request('POST', '/', [
            '_token' => $uploadToken,
            'file_type' => 'json',
            'adapter' => 'symfony',
        ], [
            'file' => $this->createUploadedFileCopy(
                __DIR__ . '/../../../packages/core/tests/data/json_invalid_missing_comma.json',
                'json_invalid_missing_comma.json',
                'application/json',
            ),
        ], [
            'HTTP_TURBO_FRAME' => 'import_step',
        ]);

        $schemaResponse = $this->followRedirect($uploadResponse, [
            'HTTP_TURBO_FRAME' => 'import_step',
        ]);
        $schemaHtml = $this->responseContent($schemaResponse);

        self::assertStringContainsString('Die JSON-Datei ist ungültig. Bitte prüfe Syntax, Kommata und Anführungszeichen.', $schemaHtml);
        self::assertStringNotContainsString('Invalid JSON:', $schemaHtml);
    }

    public function testMappingStepCanIgnoreColumnsInPreviewAndProcess(): void
    {
        $mappingResponse = $this->arriveAtMappingStep('csv', 'memory');
        $mappingHtml = $this->responseContent($mappingResponse);
        $mappingToken = $this->extractTokenForForm($mappingHtml, '/import/mapping');
        $mappingForm = $this->extractHiddenFieldsForForm($mappingHtml, '/import/mapping');

        $updatedMappingResponse = $this->request('POST', '/import/mapping', array_merge($mappingForm, [
            '_token' => $mappingToken,
            'mapping' => [
                'name' => '',
                'age' => 'age',
                'email' => 'email',
            ],
        ]), [], [
            'HTTP_TURBO_FRAME' => 'import_step',
        ]);

        self::assertSame(Response::HTTP_OK, $updatedMappingResponse->getStatusCode());

        $updatedMappingHtml = $this->responseContent($updatedMappingResponse);
        self::assertStringContainsString('<th>age</th>', $updatedMappingHtml);
        self::assertStringContainsString('<th>email</th>', $updatedMappingHtml);
        self::assertStringNotContainsString('<th>name</th>', $updatedMappingHtml);

        $processToken = $this->extractTokenForForm($updatedMappingHtml, '/import/process');
        $processForm = $this->extractHiddenFieldsForForm($updatedMappingHtml, '/import/process');
        self::assertArrayHasKey('mapping[name]', $processForm);
        self::assertSame('', $processForm['mapping[name]']);
        self::assertSame('age', $processForm['mapping[age]'] ?? null);
        self::assertSame('email', $processForm['mapping[email]'] ?? null);

        $processResponse = $this->request('POST', '/import/process', array_merge($processForm, [
            '_token' => $processToken,
        ]), [], [
            'HTTP_TURBO_FRAME' => 'import_step',
        ]);

        self::assertSame(Response::HTTP_OK, $processResponse->getStatusCode());
    }

    public function testSymfonyProcessQueuesAsyncImportInsteadOfBlocking(): void
    {
        $mappingResponse = $this->arriveAtMappingStep('csv', 'symfony');
        $mappingHtml = $this->responseContent($mappingResponse);
        $processToken = $this->extractTokenForForm($mappingHtml, '/import/process');
        $processForm = $this->extractHiddenFieldsForForm($mappingHtml, '/import/process');

        $processResponse = $this->request('POST', '/import/process', array_merge($processForm, [
            '_token' => $processToken,
        ]), [], [
            'HTTP_TURBO_FRAME' => 'import_step',
        ]);

        $resultHtml = $this->responseContent($processResponse);
        self::assertSame(Response::HTTP_OK, $processResponse->getStatusCode());
        self::assertStringContainsString('Import als Hintergrundjob gestartet', $resultHtml);
        self::assertStringContainsString('/api/imports/', $resultHtml);
        self::assertStringContainsString('messenger:consume async_imports', $resultHtml);
    }

    /**
     * @param array<string, string>       $parameters
     * @param array<string, UploadedFile> $files
     * @param array<string, string>       $server
     */
    private function request(string $method, string $uri, array $parameters = [], array $files = [], array $server = []): Response
    {
        if (! $this->session->isStarted()) {
            $this->session->start();
        }

        $request = Request::create($uri, $method, $parameters, [], $files, array_merge([
            'HTTP_HOST' => 'localhost',
        ], $server));
        $request->setSession($this->session);

        $response = $this->kernel->handle($request);
        $this->kernel->terminate($request, $response);

        return $response;
    }

    /**
     * @param array<string, string> $server
     */
    private function followRedirect(Response $response, array $server = []): Response
    {
        $location = (string) $response->headers->get('Location');
        self::assertNotSame('', $location);

        return $this->request('GET', $location, [], [], $server);
    }

    private function completeProcessStep(string $fileType, string $adapter): Response
    {
        $mappingResponse = $this->arriveAtMappingStep($fileType, $adapter);
        $mappingHtml = $this->responseContent($mappingResponse);
        $processToken = $this->extractTokenForForm($mappingHtml, '/import/process');
        $processForm = $this->extractHiddenFieldsForForm($mappingHtml, '/import/process');

        return $this->request('POST', '/import/process', array_merge($processForm, [
            '_token' => $processToken,
        ]), [], [
            'HTTP_TURBO_FRAME' => 'import_step',
        ]);
    }

    private function arriveAtMappingStep(string $fileType, string $adapter): Response
    {
        $indexResponse = $this->request('GET', '/');
        $indexHtml = $this->responseContent($indexResponse);
        $uploadToken = $this->extractTokenForForm($indexHtml, '/');
        $uploadedFile = $fileType === 'xlsx'
            ? $this->createUploadedFileCopy(
                __DIR__ . '/../../../packages/core/tests/Infrastructure/Reader/Spreadsheet/data/sample.xlsx',
                'sample.xlsx',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            )
            : $this->createUploadedFileCopy();

        $uploadResponse = $this->request('POST', '/', [
            '_token' => $uploadToken,
            'file_type' => $fileType,
            'adapter' => $adapter,
        ], [
            'file' => $uploadedFile,
        ], [
            'HTTP_TURBO_FRAME' => 'import_step',
        ]);

        self::assertSame(Response::HTTP_FOUND, $uploadResponse->getStatusCode());
        $schemaLocation = (string) $uploadResponse->headers->get('Location');
        parse_str((string) parse_url($schemaLocation, PHP_URL_QUERY), $schemaQuery);
        if (isset($schemaQuery['file']) && is_string($schemaQuery['file'])) {
            $this->cleanupFiles[] = sys_get_temp_dir() . '/' . basename($schemaQuery['file']);
        }

        $schemaResponse = $this->followRedirect($uploadResponse, [
            'HTTP_TURBO_FRAME' => 'import_step',
        ]);
        $schemaToken = $this->extractTokenForForm($this->responseContent($schemaResponse), '/import/schema');

        $schemaResponsePost = $this->request('POST', '/import/schema', [
            '_token' => $schemaToken,
            'file' => (string) ($schemaQuery['file'] ?? ''),
            'file_type' => $fileType,
            'adapter' => $adapter,
            'table' => 'imported_rows',
        ], [], [
            'HTTP_TURBO_FRAME' => 'import_step',
        ]);

        self::assertSame(Response::HTTP_FOUND, $schemaResponsePost->getStatusCode());

        return $this->followRedirect($schemaResponsePost, [
            'HTTP_TURBO_FRAME' => 'import_step',
        ]);
    }

    private function createUploadedFileCopy(
        string $source = __DIR__ . '/../data/sample.csv',
        string $clientName = 'sample.csv',
        string $mimeType = 'text/csv',
    ): UploadedFile {
        $target = tempnam(sys_get_temp_dir(), 'import_test_');
        self::assertNotFalse($target);
        copy($source, $target);
        $this->cleanupFiles[] = $target;

        return new UploadedFile($target, $clientName, $mimeType, null, true);
    }

    private function responseContent(Response $response): string
    {
        return (string) $response->getContent();
    }

    private function extractTokenForForm(string $html, string $action): string
    {
        $formHtml = $this->extractFormHtml($html, $action);
        preg_match('/name="_token"\s+value="([^"]+)"/', $formHtml, $matches);

        self::assertArrayHasKey(1, $matches, 'Expected CSRF token field was not found.');

        return html_entity_decode($matches[1], ENT_QUOTES);
    }

    /**
     * @return array<string, string>
     */
    private function extractHiddenFieldsForForm(string $html, string $action): array
    {
        $formHtml = $this->extractFormHtml($html, $action);
        preg_match_all('/<input type="hidden" name="([^"]+)" value="([^"]*)">/', $formHtml, $matches, \PREG_SET_ORDER);

        $fields = [];
        foreach ($matches as $match) {
            $fields[html_entity_decode($match[1], ENT_QUOTES)] = html_entity_decode($match[2], ENT_QUOTES);
        }

        return $fields;
    }

    private function extractFormHtml(string $html, string $action): string
    {
        $quotedAction = preg_quote($action, '/');
        $pattern = $action === ''
            ? '/<form\b(?![^>]*action=)[^>]*>(.*?)<\/form>/si'
            : sprintf('/<form\b[^>]*action="%s"[^>]*>(.*?)<\/form>/si', $quotedAction);

        preg_match($pattern, $html, $matches);
        self::assertArrayHasKey(0, $matches, sprintf('Expected form with action "%s" was not found.', $action));

        return $matches[0];
    }
}
