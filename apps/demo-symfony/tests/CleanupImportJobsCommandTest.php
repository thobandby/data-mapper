<?php

declare(strict_types=1);

namespace App\Tests;

use App\Command\CleanupImportJobsCommand;
use App\Import\Status\ImportJobClockInterface;
use App\Import\Status\ImportJobRepository;
use App\Import\Status\ImportJobSchemaManager;
use App\Import\Status\ImportJobSerializer;
use App\Import\Status\ImportJobStore;
use App\Service\ImportArtifactStorage;
use App\Service\ImportManager;
use Doctrine\DBAL\DriverManager;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

final class CleanupImportJobsCommandTest extends TestCase
{
    private string $artifactDirectory;

    /** @var list<string> */
    private array $cleanupFiles = [];

    protected function setUp(): void
    {
        $this->artifactDirectory = sys_get_temp_dir() . '/import-artifacts-test-' . bin2hex(random_bytes(4));
    }

    protected function tearDown(): void
    {
        foreach ($this->cleanupFiles as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }

        if (is_dir($this->artifactDirectory)) {
            @rmdir($this->artifactDirectory);
        }
    }

    public function testExecuteRemovesExpiredJobsAndFiles(): void
    {
        [$store, $connection] = $this->createJobStore();
        $artifactStorage = new ImportArtifactStorage($this->artifactDirectory);
        $command = new CleanupImportJobsCommand($store, $artifactStorage, new ImportManager());
        $tester = new CommandTester($command);

        $artifactPath = $artifactStorage->allocatePath('json');
        file_put_contents($artifactPath, '{}');
        $uploadName = 'cleanup-upload.csv';
        $uploadPath = sys_get_temp_dir() . '/' . $uploadName;
        file_put_contents($uploadPath, "name\nAlice\n");

        $this->cleanupFiles[] = $artifactPath;
        $this->cleanupFiles[] = $uploadPath;

        $job = $store->create([
            'id' => 'job-1',
            'status' => 'completed',
            'file' => $uploadName,
            'artifact_file' => $artifactPath,
        ]);
        $this->markJobAsExpired($connection, $job, '2026-03-01 00:00:00');

        $exitCode = $tester->execute(['--older-than-hours' => '24']);

        self::assertSame(0, $exitCode);
        self::assertStringContainsString('Removed 1 import jobs and 2 related files.', $tester->getDisplay());
        self::assertNull($store->get('job-1'));
        self::assertFileDoesNotExist($artifactPath);
        self::assertFileDoesNotExist($uploadPath);
    }

    public function testExecuteDryRunKeepsJobsAndFiles(): void
    {
        [$store, $connection] = $this->createJobStore();
        $artifactStorage = new ImportArtifactStorage($this->artifactDirectory);
        $command = new CleanupImportJobsCommand($store, $artifactStorage, new ImportManager());
        $tester = new CommandTester($command);

        $artifactPath = $artifactStorage->allocatePath('xml');
        file_put_contents($artifactPath, '<root/>');
        $uploadName = 'cleanup-dry-run.csv';
        $uploadPath = sys_get_temp_dir() . '/' . $uploadName;
        file_put_contents($uploadPath, "name\nBob\n");

        $this->cleanupFiles[] = $artifactPath;
        $this->cleanupFiles[] = $uploadPath;

        $job = $store->create([
            'id' => 'job-2',
            'status' => 'failed',
            'file' => $uploadName,
            'artifact_file' => $artifactPath,
        ]);
        $this->markJobAsExpired($connection, $job, '2026-03-01 00:00:00');

        $exitCode = $tester->execute([
            '--older-than-hours' => '24',
            '--dry-run' => true,
        ]);

        self::assertSame(0, $exitCode);
        self::assertStringContainsString('Would remove 1 import jobs and 2 related files.', $tester->getDisplay());
        self::assertNotNull($store->get('job-2'));
        self::assertFileExists($artifactPath);
        self::assertFileExists($uploadPath);
    }

    /**
     * @return array{ImportJobStore, \Doctrine\DBAL\Connection}
     */
    private function createJobStore(): array
    {
        $connection = DriverManager::getConnection([
            'driver' => 'pdo_sqlite',
            'memory' => true,
        ]);

        return [
            new ImportJobStore(
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
            ),
            $connection,
        ];
    }

    /**
     * @param array<string, mixed> $job
     */
    private function markJobAsExpired(\Doctrine\DBAL\Connection $connection, array $job, string $timestamp): void
    {
        $job['updated_at'] = $timestamp;

        $connection->update('import_jobs', [
            'updated_at' => $timestamp,
            'job_payload' => (string) json_encode($job, JSON_THROW_ON_ERROR),
        ], [
            'id' => (string) $job['id'],
        ]);
    }
}
