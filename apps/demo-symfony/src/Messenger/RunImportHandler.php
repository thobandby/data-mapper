<?php

declare(strict_types=1);

namespace App\Messenger;

use App\Import\Status\ImportJobStatus;
use App\Message\ImportStatusUpdateMessage;
use App\Message\RunImportMessage;
use App\Service\ImportExecutionService;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsMessageHandler]
final readonly class RunImportHandler
{
    public function __construct(
        private ImportExecutionService $importExecutionService,
        private MessageBusInterface $messageBus,
        #[Autowire(service: 'monolog.logger.import')]
        private LoggerInterface $logger,
    ) {
    }

    public function __invoke(RunImportMessage $message): void
    {
        $this->messageBus->dispatch(new ImportStatusUpdateMessage($message->jobId, ImportJobStatus::Processing->value));

        try {
            $execution = $this->runImport($message);
            $this->dispatchCompletedStatus($message, $execution);
            $this->logCompletedImport($message, $execution);
        } catch (\Throwable $e) {
            $this->dispatchFailedStatus($message, $e);
            $this->logFailedImport($message, $e);
        }
    }

    /**
     * @return array{result: \DynamicDataImporter\Domain\Model\ImportResult, has_artifact: bool, artifact_file: ?string}
     */
    private function runImport(RunImportMessage $message): array
    {
        return $this->importExecutionService->execute(
            $message->settings->file,
            $message->settings->fileType,
            $message->settings->adapter,
            $message->settings->tableName,
            $message->settings->mapping,
            $message->settings->delimiter,
        );
    }

    /**
     * @param array{result: \DynamicDataImporter\Domain\Model\ImportResult, has_artifact: bool, artifact_file: ?string} $execution
     */
    private function dispatchCompletedStatus(RunImportMessage $message, array $execution): void
    {
        $result = $execution['result'];

        $this->messageBus->dispatch(new ImportStatusUpdateMessage($message->jobId, ImportJobStatus::Completed->value, [
            'result' => [
                'processed' => $result->processed,
                'imported' => $result->imported,
                'errors' => array_map(static fn (object $error): array => [
                    'rowIndex' => $error->rowIndex,
                    'fieldErrors' => $error->fieldErrors,
                ], $result->errors),
            ],
            'has_artifact' => $execution['has_artifact'],
            'artifact_file' => $execution['artifact_file'],
            'error' => null,
        ]));
    }

    /**
     * @param array{result: \DynamicDataImporter\Domain\Model\ImportResult, has_artifact: bool, artifact_file: ?string} $execution
     */
    private function logCompletedImport(RunImportMessage $message, array $execution): void
    {
        $this->logger->info('Import job completed.', [
            'job_id' => $message->jobId,
            'adapter' => $message->settings->adapter,
            'file' => $message->settings->file,
            'table_name' => $message->settings->tableName,
            'has_artifact' => $execution['has_artifact'],
        ]);
    }

    private function dispatchFailedStatus(RunImportMessage $message, \Throwable $exception): void
    {
        $this->messageBus->dispatch(new ImportStatusUpdateMessage($message->jobId, ImportJobStatus::Failed->value, [
            'error' => $exception->getMessage(),
        ]));
    }

    private function logFailedImport(RunImportMessage $message, \Throwable $exception): void
    {
        $this->logger->error('Import job failed.', [
            'job_id' => $message->jobId,
            'adapter' => $message->settings->adapter,
            'file' => $message->settings->file,
            'table_name' => $message->settings->tableName,
            'exception_class' => $exception::class,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);
    }
}
