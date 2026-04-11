<?php

declare(strict_types=1);

namespace App\Tests;

use App\Entity\ImportedRow;
use App\Import\Execution\ArtifactImportTargetBuilder;
use App\Import\Execution\ImportTargetFactory;
use App\Import\Execution\MemoryImportTargetBuilder;
use App\Import\Execution\SymfonyImportTargetBuilder;
use App\Service\ImportArtifactStorage;
use App\Service\ImportProcessor;
use DynamicDataImporter\Domain\Model\Row;
use DynamicDataImporter\Port\Persistence\PersisterInterface;
use DynamicDataImporter\Port\Persistence\TableAwarePersisterInterface;
use DynamicDataImporter\Port\Reader\TabularReaderInterface;
use PHPUnit\Framework\TestCase;

final class ImportProcessorTest extends TestCase
{
    /** @var list<string> */
    private array $cleanupFiles = [];
    private string $artifactDirectory;

    protected function setUp(): void
    {
        $this->artifactDirectory = sys_get_temp_dir() . '/import-processor-artifacts-' . bin2hex(random_bytes(6));
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

    public function testSymfonyAdapterUsesGenericEntityMapperForDefaultTable(): void
    {
        $persister = new class implements PersisterInterface, TableAwarePersisterInterface {
            public string $tableName = '';

            /** @var list<object> */
            public array $entities = [];

            public function useTableName(string $name): void
            {
                $this->tableName = $name;
            }

            public function persist(object $entity): void
            {
                $this->entities[] = $entity;
            }

            public function flush(): void
            {
            }
        };

        $processor = new ImportProcessor($this->createTargetFactory($persister));

        $result = $processor->process($this->createReader([
            new Row(1, ['name' => 'Alice']),
        ]), 'symfony', 'imported_rows');

        self::assertSame('imported_rows', $persister->tableName);
        self::assertCount(1, $persister->entities);
        self::assertInstanceOf(ImportedRow::class, $persister->entities[0]);
        self::assertSame(['name' => 'Alice'], $persister->entities[0]->getData());
        self::assertSame(1, $result->processed);
        self::assertSame(1, $result->imported);
    }

    public function testSymfonyAdapterPersistsRowsDirectlyForCustomTables(): void
    {
        $persister = new class implements PersisterInterface, TableAwarePersisterInterface {
            public string $tableName = '';

            /** @var list<object> */
            public array $entities = [];

            public function useTableName(string $name): void
            {
                $this->tableName = $name;
            }

            public function persist(object $entity): void
            {
                $this->entities[] = $entity;
            }

            public function flush(): void
            {
            }
        };

        $processor = new ImportProcessor($this->createTargetFactory($persister));

        $processor->process($this->createReader([
            new Row(1, ['name' => 'Alice']),
        ]), 'symfony', 'custom_rows');

        self::assertSame('custom_rows', $persister->tableName);
        self::assertCount(1, $persister->entities);
        self::assertInstanceOf(Row::class, $persister->entities[0]);
    }

    public function testJsonAdapterWritesImportResultToJsonFileWithoutUsingDefaultPersister(): void
    {
        $defaultPersister = new class implements PersisterInterface {
            public function persist(object $entity): void
            {
                throw new \RuntimeException('Default persister should not be used.');
            }

            public function flush(): void
            {
                throw new \RuntimeException('Default persister should not be used.');
            }
        };

        $processor = new ImportProcessor($this->createTargetFactory($defaultPersister));

        $processedImport = $processor->processWithMetadata($this->createReader([
            new Row(1, ['name' => 'Alice']),
        ]), 'json', 'ignored');

        self::assertSame(1, $processedImport->result->processed);
        self::assertSame(1, $processedImport->result->imported);
        self::assertNotNull($processedImport->artifactPath);
        $this->cleanupFiles[] = $processedImport->artifactPath;
        self::assertFileExists($processedImport->artifactPath);
        self::assertSame('[{"name":"Alice"}]', preg_replace('/\s+/', '', (string) file_get_contents($processedImport->artifactPath)));
    }

    public function testFallbackAdapterUsesInMemoryPersistencePath(): void
    {
        $defaultPersister = new class implements PersisterInterface {
            public function persist(object $entity): void
            {
                throw new \RuntimeException('Default persister should not be used.');
            }

            public function flush(): void
            {
                throw new \RuntimeException('Default persister should not be used.');
            }
        };

        $processor = new ImportProcessor($this->createTargetFactory($defaultPersister));

        $result = $processor->process($this->createReader([
            new Row(1, ['name' => 'Alice']),
            new Row(2, ['name' => 'Bob']),
        ]), 'memory', 'ignored');

        self::assertSame(2, $result->processed);
        self::assertSame(2, $result->imported);
        self::assertCount(0, $result->errors);
    }

    /**
     * @param list<Row> $rows
     */
    private function createReader(array $rows): TabularReaderInterface
    {
        return new class($rows) implements TabularReaderInterface {
            /**
             * @param list<Row> $rows
             */
            public function __construct(
                private readonly array $rows,
            ) {
            }

            public function headers(): array
            {
                return [];
            }

            public function rows(): iterable
            {
                return $this->rows;
            }
        };
    }

    private function createTargetFactory(PersisterInterface $defaultPersister): ImportTargetFactory
    {
        $artifactStorage = new ImportArtifactStorage($this->artifactDirectory);

        return new ImportTargetFactory([
            new SymfonyImportTargetBuilder($defaultPersister),
            new ArtifactImportTargetBuilder($artifactStorage),
            new MemoryImportTargetBuilder(),
        ]);
    }
}
