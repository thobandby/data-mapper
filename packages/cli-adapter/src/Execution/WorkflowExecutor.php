<?php

declare(strict_types=1);

namespace DynamicDataImporter\Cli\Execution;

use DynamicDataImporter\Application\Service\ImportWorkflowService;
use DynamicDataImporter\Cli\Contract\WorkflowExecutorInterface;
use DynamicDataImporter\Cli\Input\CliOptions;

final readonly class WorkflowExecutor implements WorkflowExecutorInterface
{
    public function __construct(private ImportWorkflowService $workflow)
    {
    }

    /**
     * @return array<string, mixed>
     */
    public function analyze(CliOptions $options): array
    {
        return $this->workflow->analyze(
            $options->filePath ?? '',
            $options->fileType,
            $options->delimiter,
            $options->sampleSize,
            $options->mapping,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function preview(CliOptions $options): array
    {
        return $this->workflow->preview(
            $options->filePath ?? '',
            $options->fileType,
            $options->delimiter,
            $options->sampleSize,
            $options->mapping,
        );
    }

    /**
     * @param callable(int): void $progressCallback
     *
     * @return array<string, mixed>
     */
    public function execute(CliOptions $options, callable $progressCallback): array
    {
        return $this->workflow->execute(
            $options->filePath ?? '',
            $options->fileType,
            $options->delimiter,
            $options->mapping,
            $options->dryRun ? 'memory' : $options->outputFormat,
            $options->tableName,
            $progressCallback,
        );
    }
}
