<?php

declare(strict_types=1);

namespace App\Tests;

use App\Import\Status\ImportJobClockInterface;
use App\Import\Status\ImportJobRepository;
use App\Import\Status\ImportJobSchemaManager;
use App\Import\Status\ImportJobSerializer;
use App\Import\Status\ImportJobStore;
use Doctrine\DBAL\DriverManager;
use PHPUnit\Framework\TestCase;

final class ImportJobStoreTest extends TestCase
{
    public function testCreateAndUpdateJob(): void
    {
        $connection = DriverManager::getConnection([
            'driver' => 'pdo_sqlite',
            'memory' => true,
        ]);

        $store = new ImportJobStore(
            new ImportJobRepository(
                $connection,
                new ImportJobSchemaManager($connection),
                new ImportJobSerializer(),
            ),
            new class implements ImportJobClockInterface {
                public function now(): \DateTimeImmutable
                {
                    return new \DateTimeImmutable('2026-04-05 12:00:00', new \DateTimeZone('UTC'));
                }
            },
        );

        $job = $store->create([
            'id' => 'job-1',
            'status' => 'queued',
            'adapter' => 'symfony',
        ]);

        self::assertSame('queued', $job['status']);
        self::assertNotNull($job['created_at']);

        $updated = $store->update('job-1', 'completed', [
            'result' => ['processed' => 10, 'imported' => 9, 'errors' => []],
        ]);

        self::assertSame('completed', $updated['status']);
        self::assertSame(10, $updated['result']['processed']);
        self::assertSame($updated, $store->get('job-1'));
    }
}
