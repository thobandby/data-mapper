<?php

declare(strict_types=1);

namespace App\Import\Status;

use Psr\Log\LoggerInterface;

final class ImportJobStore
{
    public function __construct(
        private readonly ImportJobRepository $repository,
        private readonly ImportJobClockInterface $clock,
        private readonly ?LoggerInterface $logger = null,
    ) {
    }

    /**
     * @param array<string, mixed> $job
     *
     * @return array<string, mixed>
     */
    public function create(array $job): array
    {
        $timestamp = $this->timestamp();
        $job['created_at'] ??= $timestamp;
        $job['updated_at'] = $timestamp;

        $result = $this->repository->create($job);
        $this->logger?->info('Import job created.', ['job_id' => $job['id'], 'status' => $job['status']]);

        return $result;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function get(string $jobId): ?array
    {
        return $this->repository->get($jobId);
    }

    /**
     * @param array<string, mixed> $payload
     *
     * @return array<string, mixed>|null
     */
    public function update(string $jobId, string $status, array $payload = []): ?array
    {
        $job = $this->get($jobId);
        if ($job === null) {
            return null;
        }

        $job['status'] = $status;
        $job['updated_at'] = $this->timestamp();

        foreach ($payload as $key => $value) {
            $job[$key] = $value;
        }

        $this->repository->save($jobId, $job);
        $this->logger?->info('Import job updated.', ['job_id' => $jobId, 'status' => $status]);

        return $job;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function findExpiredTerminalJobs(\DateTimeImmutable $olderThan): array
    {
        return $this->repository->findExpiredTerminalJobs($olderThan);
    }

    public function delete(string $jobId): void
    {
        $this->repository->delete($jobId);
        $this->logger?->info('Import job deleted.', ['job_id' => $jobId]);
    }

    private function timestamp(): string
    {
        return $this->clock->now()->setTimezone(new \DateTimeZone('UTC'))->format('Y-m-d H:i:s');
    }
}
