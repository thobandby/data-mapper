<?php

declare(strict_types=1);

namespace DynamicDataImporter\Cli\Application;

use DynamicDataImporter\Cli\Contract\OutputFormatterInterface;
use DynamicDataImporter\Cli\Exception\CliUsageException;
use DynamicDataImporter\Cli\Input\CliOptions;

final readonly class CliExceptionPresenter
{
    private OutputFormatterInterface $formatter;
    private \Closure $stderr;

    public function __construct(OutputFormatterInterface $formatter, callable $stderr)
    {
        $this->formatter = $formatter;
        $this->stderr = \Closure::fromCallable($stderr);
    }

    public function presentUsageError(CliUsageException $exception): void
    {
        ($this->stderr)("Usage error: {$exception->getMessage()}\n\n");
        ($this->stderr)($this->formatter->usage());
    }

    public function presentRuntimeError(\Throwable $exception, ?CliOptions $options): void
    {
        ($this->stderr)(sprintf("Error: %s\n", $exception->getMessage()));

        if ($options?->verbose) {
            ($this->stderr)(sprintf("\nStacktrace:\n%s\n", $exception->getTraceAsString()));
        }
    }
}
