<?php

declare(strict_types=1);

namespace DynamicDataImporter\Cli\Execution;

use DynamicDataImporter\Cli\Contract\WorkflowExecutorInterface;
use DynamicDataImporter\Cli\Exception\CliUsageException;
use DynamicDataImporter\Cli\Input\CliOptions;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Output\OutputInterface;

final readonly class CliActionExecutor
{
    public function __construct(
        private WorkflowExecutorInterface $workflow,
        private OutputInterface $output,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function execute(CliOptions $options): array
    {
        return match ($options->action) {
            'analyze' => $this->workflow->analyze($options),
            'preview' => $this->workflow->preview($options),
            'execute' => $this->executeImport($options),
            default => throw new CliUsageException(\sprintf('Unsupported action: %s', $options->action)),
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function executeImport(CliOptions $options): array
    {
        $progressBar = new ProgressBar($this->output);
        $progressBar->setFormat(' %current% rows [%bar%] %elapsed:6s%');
        $progressBar->start();

        try {
            return $this->workflow->execute(
                $options,
                static function (int $processed) use ($progressBar): void {
                    $progressBar->setProgress($processed);
                },
            );
        } finally {
            $progressBar->finish();
            $this->output->write("\n");
        }
    }
}
