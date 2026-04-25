<?php

declare(strict_types=1);

namespace DynamicDataImporter\Tests\Pdo\Persistence;

use DynamicDataImporter\Domain\Model\Row;
use DynamicDataImporter\Pdo\Persistence\PdoPersister;
use PHPUnit\Framework\TestCase;

final class PdoPersisterTest extends TestCase
{
    public function testPersistsRowDataIntoCustomTable(): void
    {
        $pdo = new \PDO('sqlite::memory:');
        $pdo->exec('CREATE TABLE custom_rows (name TEXT, age INTEGER)');

        $persister = new PdoPersister($pdo);
        $persister->useTableName('custom_rows');
        $persister->persist(new Row(1, ['name' => 'Alice', 'age' => 30]));
        $persister->flush();

        $row = $pdo->query('SELECT name, age FROM custom_rows')->fetch(\PDO::FETCH_ASSOC);

        self::assertSame(['name' => 'Alice', 'age' => 30], $row);
    }

    public function testPersistsEntitiesViaGettersAndSkipsNullIds(): void
    {
        $pdo = new \PDO('sqlite::memory:');
        $pdo->exec('CREATE TABLE imported_rows (id INTEGER PRIMARY KEY AUTOINCREMENT, data TEXT, imported_at TEXT)');

        $entity = new class {
            public function getId(): ?int
            {
                return null;
            }

            /**
             * @return array<string, string>
             */
            public function getData(): array
            {
                return ['name' => 'Alice'];
            }

            public function getImportedAt(): \DateTimeImmutable
            {
                return new \DateTimeImmutable('2026-04-25 12:00:00');
            }
        };

        $persister = new PdoPersister($pdo);
        $persister->persist($entity);
        $persister->flush();

        $row = $pdo->query('SELECT data, imported_at FROM imported_rows')->fetch(\PDO::FETCH_ASSOC);

        self::assertSame('{"name":"Alice"}', $row['data']);
        self::assertSame('2026-04-25 12:00:00', $row['imported_at']);
    }
}
