<?php

declare(strict_types=1);

namespace App\Tests;

use App\Entity\ImportedRow;
use App\Import\Execution\ArtifactImportTargetBuilder;
use App\Import\Execution\ImportTargetFactory;
use App\Import\Execution\MemoryImportTargetBuilder;
use App\Import\Execution\SymfonyImportTargetBuilder;
use App\Service\ImportArtifactStorage;
use DynamicDataImporter\Domain\Exception\ImporterException;
use DynamicDataImporter\Domain\Model\Row;
use DynamicDataImporter\Port\Persistence\PersisterInterface;
use DynamicDataImporter\Port\Persistence\TableAwarePersisterInterface;
use DynamicDataImporter\Port\Reader\TabularReaderInterface;
use PHPUnit\Framework\TestCase;

final class ImportTargetFactoryTest extends TestCase
{
    /** @var list<string> */
    private array $cleanupFiles = [];
    private string $artifactDirectory;

    protected function setUp(): void
    {
        $this->artifactDirectory = sys_get_temp_dir() . '/import-artifacts-test-' . bin2hex(random_bytes(6));
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

    public function testCreateImporterUsesDefaultPersisterAndGenericEntityMapperForDefaultSymfonyTable(): void
    {
        $persister = new class implements PersisterInterface, TableAwarePersisterInterface {
            public string $tableName = '';

            /** @var list<object> */
            public array $entities = [];

            public function useTableName(string $tableName): void
            {
                $this->tableName = $tableName;
            }

            public function persist(object $entity): void
            {
                $this->entities[] = $entity;
            }

            public function flush(): void
            {
            }
        };

        $factory = $this->createFactory($persister);
        $importer = $factory->createImporter('symfony', 'imported_rows');
        $result = $importer($this->createReader([new Row(1, ['name' => 'Alice'])]));

        self::assertSame('imported_rows', $persister->tableName);
        self::assertCount(1, $persister->entities);
        self::assertInstanceOf(ImportedRow::class, $persister->entities[0]);
        self::assertSame(1, $result->imported);
    }

    public function testCreateImporterUsesDefaultPersisterWithoutEntityMapperForCustomSymfonyTable(): void
    {
        $persister = new class implements PersisterInterface, TableAwarePersisterInterface {
            public string $tableName = '';

            /** @var list<object> */
            public array $entities = [];

            public function useTableName(string $tableName): void
            {
                $this->tableName = $tableName;
            }

            public function persist(object $entity): void
            {
                $this->entities[] = $entity;
            }

            public function flush(): void
            {
            }
        };

        $factory = $this->createFactory($persister);
        $importer = $factory->createImporter('symfony', 'custom_rows');
        $importer($this->createReader([new Row(1, ['name' => 'Alice'])]));

        self::assertSame('custom_rows', $persister->tableName);
        self::assertCount(1, $persister->entities);
        self::assertInstanceOf(Row::class, $persister->entities[0]);
    }

    public function testCreateImporterUsesJsonPersisterForJsonAdapter(): void
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

        $factory = $this->createFactory($defaultPersister);
        $target = $factory->createTarget('json', 'ignored');
        $result = $target->importFile->__invoke($this->createReader([new Row(1, ['name' => 'Alice'])]));

        self::assertSame(1, $result->imported);
        self::assertNotNull($target->artifactPath);
        $this->cleanupFiles[] = $target->artifactPath;
        self::assertFileExists($target->artifactPath);
        self::assertSame('[{"name":"Alice"}]', preg_replace('/\s+/', '', (string) file_get_contents($target->artifactPath)));
    }

    public function testCreateImporterUsesSqlPersisterForSqlAdapter(): void
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

        $factory = $this->createFactory($defaultPersister);
        $target = $factory->createTarget('sql', 'imported_rows');
        $result = $target->importFile->__invoke($this->createReader([new Row(1, ['name' => 'Alice'])]));

        self::assertSame(1, $result->imported);
        self::assertNotNull($target->artifactPath);
        $this->cleanupFiles[] = $target->artifactPath;
        self::assertFileExists($target->artifactPath);
        self::assertStringContainsString('CREATE TABLE "imported_rows" ("name" TEXT);', (string) file_get_contents($target->artifactPath));
        self::assertStringContainsString('INSERT INTO "imported_rows" ("name") VALUES (\'Alice\');', (string) file_get_contents($target->artifactPath));
    }

    public function testCreateImporterUsesXmlPersisterForXmlAdapter(): void
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

        $factory = $this->createFactory($defaultPersister);
        $target = $factory->createTarget('xml', 'ignored');
        $result = $target->importFile->__invoke($this->createReader([new Row(1, ['name' => 'Alice'])]));

        self::assertSame(1, $result->imported);
        self::assertNotNull($target->artifactPath);
        $this->cleanupFiles[] = $target->artifactPath;
        self::assertFileExists($target->artifactPath);
        self::assertStringContainsString('<name>Alice</name>', (string) file_get_contents($target->artifactPath));
    }

    public function testCreateImporterUsesInMemoryPersisterForFallbackAdapters(): void
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

        $factory = $this->createFactory($defaultPersister);
        $importer = $factory->createImporter('memory', 'ignored');
        $result = $importer($this->createReader([
            new Row(1, ['name' => 'Alice']),
            new Row(2, ['name' => 'Bob']),
        ]));

        self::assertSame(2, $result->processed);
        self::assertSame(2, $result->imported);
    }

    public function testCreatesArtifactReturnsTrueForJsonXmlAndSqlAdapters(): void
    {
        $factory = $this->createFactory($this->createMock(PersisterInterface::class));

        self::assertTrue($factory->createsArtifact('json'));
        self::assertTrue($factory->createsArtifact('xml'));
        self::assertTrue($factory->createsArtifact('sql'));
        self::assertFalse($factory->createsArtifact('symfony'));
        self::assertFalse($factory->createsArtifact('memory'));
    }

    public function testCreateTargetRejectsUnsupportedAdapter(): void
    {
        $factory = $this->createFactory($this->createMock(PersisterInterface::class));

        $this->expectException(ImporterException::class);
        $this->expectExceptionMessage('Unsupported adapter: bogus');

        $factory->createTarget('bogus', 'ignored');
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

    private function createFactory(PersisterInterface $defaultPersister): ImportTargetFactory
    {
        $artifactStorage = new ImportArtifactStorage($this->artifactDirectory);

        return new ImportTargetFactory([
            new SymfonyImportTargetBuilder($defaultPersister),
            new ArtifactImportTargetBuilder($artifactStorage),
            new MemoryImportTargetBuilder(),
        ]);
    }
}
