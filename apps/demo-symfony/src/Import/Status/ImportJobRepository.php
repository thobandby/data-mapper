<?php

declare(strict_types=1);

namespace App\Import\Status;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;

final class ImportJobRepository
{
    private const string TABLE_NAME = 'import_jobs';

    public function __construct(
        private readonly Connection $connection,
        private readonly ImportJobSchemaManager $schemaManager,
        private readonly ImportJobSerializer $serializer,
    ) {
    }

    /**
     * @param array<string, mixed> $job
     *
     * @return array<string, mixed>
     */
    public function create(array $job): array
    {
        $this->schemaManager->ensureSchema();

        $this->connection->insert(self::TABLE_NAME, [
            'id' => (string) $job['id'],
            'status' => (string) $job['status'],
            'job_payload' => $this->serializer->encode($job),
            'created_at' => (string) $job['created_at'],
            'updated_at' => (string) $job['updated_at'],
        ]);

        return $job;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function get(string $jobId): ?array
    {
        $this->schemaManager->ensureSchema();

        $row = $this->connection->fetchAssociative(
            sprintf('SELECT job_payload FROM %s WHERE id = :id', self::TABLE_NAME),
            ['id' => $jobId],
            ['id' => ParameterType::STRING],
        );

        if (! is_array($row)) {
            return null;
        }

        return $this->serializer->decode($row['job_payload'] ?? null);
    }

    /**
     * @param array<string, mixed> $job
     */
    public function save(string $jobId, array $job): void
    {
        $this->schemaManager->ensureSchema();

        $this->connection->update(self::TABLE_NAME, [
            'status' => (string) $job['status'],
            'job_payload' => $this->serializer->encode($job),
            'updated_at' => (string) $job['updated_at'],
        ], [
            'id' => $jobId,
        ]);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function findExpiredTerminalJobs(\DateTimeImmutable $olderThan): array
    {
        $this->schemaManager->ensureSchema();

        $rows = $this->connection->fetchAllAssociative(
            sprintf(
                'SELECT job_payload FROM %s WHERE status IN (:statuses) AND updated_at < :updated_at',
                self::TABLE_NAME,
            ),
            [
                'statuses' => [ImportJobStatus::Completed->value, ImportJobStatus::Failed->value],
                'updated_at' => $olderThan->setTimezone(new \DateTimeZone('UTC'))->format('Y-m-d H:i:s'),
            ],
            [
                'statuses' => ArrayParameterType::STRING,
                'updated_at' => ParameterType::STRING,
            ],
        );

        $jobs = [];

        foreach ($rows as $row) {
            $job = $this->serializer->decode($row['job_payload'] ?? null);
            if ($job !== null) {
                $jobs[] = $job;
            }
        }

        return $jobs;
    }

    public function delete(string $jobId): void
    {
        $this->schemaManager->ensureSchema();
        $this->connection->delete(self::TABLE_NAME, ['id' => $jobId]);
    }
}
