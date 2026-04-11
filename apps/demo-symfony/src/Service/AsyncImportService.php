<?php

declare(strict_types=1);

namespace App\Service;

use App\Import\Status\ImportJobStatus;
use App\Import\Status\ImportJobStore;
use App\Message\ImportSettings;
use App\Message\RunImportMessage;
use Symfony\Component\Messenger\MessageBusInterface;

final readonly class AsyncImportService
{
    public function __construct(
        private MessageBusInterface $messageBus,
        private ImportJobStore $importJobStore,
    ) {
    }

    /**
     * @param array<string, string> $mapping
     *
     * @return array<string, mixed>
     */
    public function dispatch(
        string $file,
        string $fileType,
        string $adapter,
        string $tableName,
        array $mapping,
        ?string $delimiter,
    ): array {
        $job = $this->importJobStore->create(
            $this->newJobPayload($file, $fileType, $adapter, $tableName, $mapping, $delimiter)
        );
        $this->messageBus->dispatch($this->newRunImportMessage($job, $file, $fileType, $adapter, $tableName, $mapping, $delimiter));

        return $job;
    }

    /**
     * @param array<string, string> $mapping
     *
     * @return array<string, mixed>
     */
    private function newJobPayload(
        string $file,
        string $fileType,
        string $adapter,
        string $tableName,
        array $mapping,
        ?string $delimiter,
    ): array {
        return [
            'id' => bin2hex(random_bytes(16)),
            'status' => ImportJobStatus::Queued->value,
            'file' => $file,
            'file_type' => $fileType,
            'adapter' => $adapter,
            'table_name' => $tableName,
            'mapping' => $mapping,
            'delimiter' => $delimiter,
            'result' => null,
            'has_artifact' => false,
            'artifact_file' => null,
            'error' => null,
        ];
    }

    /**
     * @param array<string, mixed>  $job
     * @param array<string, string> $mapping
     */
    private function newRunImportMessage(
        array $job,
        string $file,
        string $fileType,
        string $adapter,
        string $tableName,
        array $mapping,
        ?string $delimiter,
    ): RunImportMessage {
        return new RunImportMessage(
            (string) $job['id'],
            new ImportSettings($file, $fileType, $adapter, $tableName, $mapping, $delimiter),
        );
    }
}
