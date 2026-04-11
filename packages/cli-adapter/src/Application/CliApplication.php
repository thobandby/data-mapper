<?php

declare(strict_types=1);

namespace DynamicDataImporter\Cli\Application;

use DynamicDataImporter\Application\Service\ImportWorkflowService;
use DynamicDataImporter\Cli\Infrastructure\Console\CliConsoleIoFactory;
use Symfony\Component\Console\Output\OutputInterface;

final class CliApplication
{
    public const NONE_LABEL = '(none)';
    public const FILE_TYPE_LINE = 'File type: %s';
    public const DELIMITER_LINE = 'Delimiter: %s';

    /** @var callable(string): void */
    private $stderr;

    private readonly OutputInterface $output;
    private readonly \DynamicDataImporter\Cli\Contract\OutputFormatterInterface $formatter;
    private readonly CliApplicationRunner $runner;

    public function __construct(
        private readonly ImportWorkflowService $workflow = new ImportWorkflowService(),
        ?callable $stderr = null,
        ?OutputInterface $output = null,
    ) {
        $resolvedOutput = CliConsoleIoFactory::createOutput($output);
        $this->output = $resolvedOutput;
        $this->stderr = CliConsoleIoFactory::createStderr($stderr);
        $factory = new CliApplicationFactory();
        $this->formatter = $factory->createFormatter($resolvedOutput, $this->writeStdout(...));
        $this->runner = $factory->createRunner(
            workflow: $this->workflow,
            output: $resolvedOutput,
            stdout: $this->writeStdout(...),
            stderr: $this->writeStderr(...),
        );
    }

    /**
     * @param list<string> $argv
     */
    public function run(array $argv): int
    {
        return $this->runner->run($argv);
    }

    public function usage(): string
    {
        return $this->formatter->usage();
    }

    private function writeStdout(string $message): void
    {
        $this->output->write($message);
    }

    private function writeStderr(string $message): void
    {
        ($this->stderr)($message);
    }
}
