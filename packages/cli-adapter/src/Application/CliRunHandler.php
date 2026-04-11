<?php

declare(strict_types=1);

namespace DynamicDataImporter\Cli\Application;

use DynamicDataImporter\Cli\Contract\ArtifactWriterInterface;
use DynamicDataImporter\Cli\Contract\OutputFormatterInterface;
use DynamicDataImporter\Cli\Contract\WizardInterface;
use DynamicDataImporter\Cli\Execution\CliActionExecutor;
use DynamicDataImporter\Cli\Input\CliOptions;

final readonly class CliRunHandler
{
    private \Closure $stdout;

    public function __construct(
        private WizardInterface $wizard,
        private CliActionExecutor $actionExecutor,
        private ArtifactWriterInterface $artifactWriter,
        private OutputFormatterInterface $formatter,
        callable $stdout,
    ) {
        $this->stdout = \Closure::fromCallable($stdout);
    }

    public function handle(CliOptions $options): void
    {
        if ($options->action === 'help') {
            ($this->stdout)($this->formatter->usage());

            return;
        }

        $resolvedOptions = $options->action === 'wizard' ? $this->wizard->run() : $options;
        $payload = $this->actionExecutor->execute($resolvedOptions);
        $this->artifactWriter->write($payload, $resolvedOptions);
        ($this->stdout)($this->formatter->formatPayload($resolvedOptions, $payload));
    }
}
