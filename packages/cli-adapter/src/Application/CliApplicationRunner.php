<?php

declare(strict_types=1);

namespace DynamicDataImporter\Cli\Application;

use DynamicDataImporter\Cli\Exception\CliUsageException;
use DynamicDataImporter\Cli\Input\CliOptions;
use DynamicDataImporter\Cli\Input\CliOptionsParser;

final readonly class CliApplicationRunner
{
    public function __construct(
        private CliOptionsParser $parser,
        private CliRunHandler $runHandler,
        private CliExceptionPresenter $exceptionPresenter,
    ) {
    }

    /**
     * @param list<string> $argv
     */
    public function run(array $argv): int
    {
        try {
            $this->runHandler->handle($this->parser->parse($argv));
        } catch (CliUsageException $exception) {
            $this->exceptionPresenter->presentUsageError($exception);

            return 2;
        } catch (\Throwable $exception) {
            $this->exceptionPresenter->presentRuntimeError($exception, $this->parserErrorContext($argv));

            return 1;
        }

        return 0;
    }

    /**
     * @param list<string> $argv
     */
    private function parserErrorContext(array $argv): ?CliOptions
    {
        try {
            return $this->parser->parse($argv);
        } catch (\Throwable) {
            return null;
        }
    }
}
