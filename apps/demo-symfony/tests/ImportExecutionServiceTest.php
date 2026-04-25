<?php

declare(strict_types=1);

namespace App\Tests;

use App\Import\Execution\ArtifactImportTargetBuilder;
use App\Import\Execution\ImportTargetFactory;
use App\Import\Execution\MemoryImportTargetBuilder;
use App\Import\Execution\PdoImportTargetBuilder;
use App\Import\Execution\ProcessedImport;
use App\Import\Execution\SymfonyImportTargetBuilder;
use App\Service\ImportArtifactStorage;
use App\Service\ImportExecutionService;
use App\Service\ImportManager;
use App\Service\ImportProcessor;
use App\Service\ImportReaderFactory;
use Doctrine\DBAL\Connection;
use DynamicDataImporter\Domain\Exception\ImporterException;
use DynamicDataImporter\Domain\Model\ImportResult;
use DynamicDataImporter\Port\Reader\TabularReaderInterface;
use PHPUnit\Framework\TestCase;

final class ImportExecutionServiceTest extends TestCase
{
    /** @var list<string> */
    private array $cleanupFiles = [];
    private string $artifactDirectory;

    protected function setUp(): void
    {
        $this->artifactDirectory = sys_get_temp_dir() . '/import-execution-artifacts-' . bin2hex(random_bytes(6));
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

    public function testExecuteThrowsWhenResolvedFileDoesNotExist(): void
    {
        $service = new ImportExecutionService(
            $importManager = $this->createMock(ImportManager::class),
            new ImportReaderFactory(),
            $this->createMock(ImportProcessor::class),
            $this->createTargetFactory(),
        );

        $importManager->method('getFilePath')->with('missing.csv')->willReturn('/tmp/missing-import.csv');

        $this->expectException(ImporterException::class);
        $this->expectExceptionMessage('File not found.');

        try {
            $service->execute('missing.csv', 'csv', 'memory', 'ignored', [], null);
        } catch (ImporterException $e) {
            self::assertSame('file_not_found', $e->codeName());
            self::assertSame('/tmp/missing-import.csv', $e->context()['file_path'] ?? null);
            throw $e;
        }
    }

    public function testExecutePassesResolvedDelimiterAndReaderToDependencies(): void
    {
        $file = tempnam(sys_get_temp_dir(), 'import_exec_');
        self::assertNotFalse($file);
        $this->cleanupFiles[] = $file;

        $file = tempnam(sys_get_temp_dir(), 'import_exec_');
        self::assertNotFalse($file);
        file_put_contents($file, "source\nAlice\n");
        $this->cleanupFiles[] = $file;

        $result = new ImportResult(2, 2, []);

        $importManager = $this->createMock(ImportManager::class);
        $importManager->method('getFilePath')->with('stored.csv')->willReturn($file);

        $importReaderFactory = new ImportReaderFactory();

        $importProcessor = $this->createMock(ImportProcessor::class);
        $importProcessor->expects(self::once())
            ->method('processWithMetadata')
            ->with(
                self::callback(static fn (TabularReaderInterface $reader): bool => $reader->headers() === ['target']),
                'memory',
                'rows'
            )
            ->willReturn(new ProcessedImport($result));

        $service = new ImportExecutionService(
            $importManager,
            $importReaderFactory,
            $importProcessor,
            $this->createTargetFactory(),
        );

        $execution = $service->execute('stored.csv', 'csv', 'memory', 'rows', ['source' => 'target'], null);

        self::assertSame($result, $execution['result']);
        self::assertFalse($execution['has_artifact']);
        self::assertNull($execution['artifact_file']);
    }

    public function testExecuteUsesTargetFactoryToDetermineArtifactPersistenceFlag(): void
    {
        $file = tempnam(sys_get_temp_dir(), 'import_exec_json_');
        self::assertNotFalse($file);
        $this->cleanupFiles[] = $file;

        $file = tempnam(sys_get_temp_dir(), 'import_exec_json_');
        self::assertNotFalse($file);
        file_put_contents($file, "name\nAlice\n");
        $this->cleanupFiles[] = $file;

        $result = new ImportResult(1, 1, []);

        $importManager = $this->createMock(ImportManager::class);
        $importManager->method('getFilePath')->willReturn($file);

        $artifact = tempnam(sys_get_temp_dir(), 'import_exec_result_');
        self::assertNotFalse($artifact);
        $this->cleanupFiles[] = $artifact;

        $importProcessor = $this->createMock(ImportProcessor::class);
        $importProcessor->expects(self::once())
            ->method('processWithMetadata')
            ->with(
                self::callback(static fn (TabularReaderInterface $reader): bool => $reader->headers() === ['name']),
                'json',
                'rows'
            )
            ->willReturn(new ProcessedImport($result, $artifact));

        $service = new ImportExecutionService(
            $importManager,
            new ImportReaderFactory(),
            $importProcessor,
            $this->createTargetFactory(),
        );

        $execution = $service->execute('stored.csv', 'csv', 'json', 'rows', [], ',');

        self::assertSame($result, $execution['result']);
        self::assertTrue($execution['has_artifact']);
        self::assertSame($artifact, $execution['artifact_file']);
    }

    public function testExecuteBuildsSqlArtifactWithCreateTableUsingMappedColumns(): void
    {
        $file = tempnam(sys_get_temp_dir(), 'import_exec_sql_');
        self::assertNotFalse($file);
        file_put_contents($file, "name,age,email\nAlice,30,alice@example.com\n");
        $this->cleanupFiles[] = $file;

        $importManager = $this->createMock(ImportManager::class);
        $importManager->method('getFilePath')->with('stored.csv')->willReturn($file);

        $service = new ImportExecutionService(
            $importManager,
            new ImportReaderFactory(),
            new ImportProcessor($this->createTargetFactory()),
            $this->createTargetFactory(),
        );

        $execution = $service->execute('stored.csv', 'csv', 'sql', 'imported_rows', ['name' => 'full_name'], ',');

        self::assertTrue($execution['has_artifact']);
        self::assertNotNull($execution['artifact_file']);
        $this->cleanupFiles[] = $execution['artifact_file'];

        $sql = (string) file_get_contents($execution['artifact_file']);

        self::assertStringContainsString('CREATE TABLE "imported_rows" ("full_name" TEXT, "age" TEXT, "email" TEXT);', $sql);
        self::assertStringContainsString('INSERT INTO "imported_rows" ("full_name", "age", "email") VALUES (\'Alice\', 30, \'alice@example.com\');', $sql);
    }

    public function testExecuteBuildsFlatXmlArtifactUsingMappedColumns(): void
    {
        $file = tempnam(sys_get_temp_dir(), 'import_exec_xml_');
        self::assertNotFalse($file);
        file_put_contents($file, "name,age,email\nAlice,30,alice@example.com\n");
        $this->cleanupFiles[] = $file;

        $importManager = $this->createMock(ImportManager::class);
        $importManager->method('getFilePath')->with('stored.csv')->willReturn($file);

        $service = new ImportExecutionService(
            $importManager,
            new ImportReaderFactory(),
            new ImportProcessor($this->createTargetFactory()),
            $this->createTargetFactory(),
        );

        $execution = $service->execute('stored.csv', 'csv', 'xml', 'ignored', ['name' => 'full_name'], ',');

        self::assertTrue($execution['has_artifact']);
        self::assertNotNull($execution['artifact_file']);
        $this->cleanupFiles[] = $execution['artifact_file'];

        $xml = (string) file_get_contents($execution['artifact_file']);

        self::assertStringContainsString('<full_name>Alice</full_name>', $xml);
        self::assertStringContainsString('<age>30</age>', $xml);
        self::assertStringContainsString('<email>alice@example.com</email>', $xml);
    }

    public function testExecuteRejectsUnsupportedAdapter(): void
    {
        $file = tempnam(sys_get_temp_dir(), 'import_exec_invalid_adapter_');
        self::assertNotFalse($file);
        file_put_contents($file, "name\nAlice\n");
        $this->cleanupFiles[] = $file;

        $importManager = $this->createMock(ImportManager::class);
        $importManager->method('getFilePath')->with('stored.csv')->willReturn($file);

        $importProcessor = $this->createMock(ImportProcessor::class);
        $importProcessor->expects(self::never())->method('processWithMetadata');

        $service = new ImportExecutionService(
            $importManager,
            new ImportReaderFactory(),
            $importProcessor,
            $this->createTargetFactory(),
        );

        $this->expectException(ImporterException::class);
        $this->expectExceptionMessage('Unsupported adapter: bogus');

        $service->execute('stored.csv', 'csv', 'bogus', 'rows', [], null);
    }

    private function createTargetFactory(): ImportTargetFactory
    {
        $defaultPersister = $this->createMock(\DynamicDataImporter\Port\Persistence\PersisterInterface::class);
        $artifactStorage = new ImportArtifactStorage($this->artifactDirectory);
        $pdo = new \PDO('sqlite::memory:');
        $pdo->exec('CREATE TABLE imported_rows (id INTEGER PRIMARY KEY AUTOINCREMENT, data TEXT, imported_at TEXT)');
        $connection = $this->createMock(Connection::class);
        $connection->method('getNativeConnection')->willReturn($pdo);

        return new ImportTargetFactory([
            new SymfonyImportTargetBuilder($defaultPersister),
            new PdoImportTargetBuilder($connection),
            new ArtifactImportTargetBuilder($artifactStorage),
            new MemoryImportTargetBuilder(),
        ]);
    }
}
