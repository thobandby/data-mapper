<?php

declare(strict_types=1);

namespace DynamicDataImporter\Cli\Input\Mapping;

use DynamicDataImporter\Cli\Exception\CliUsageException;
use DynamicDataImporter\Cli\Input\CliOptionsParser;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;

final readonly class CliMappingEntryCollector
{
    private \Closure $stderr;

    public function __construct(
        private CliOptionsParser $parser,
        private OutputInterface $output,
        callable $stderr,
    ) {
        $this->stderr = \Closure::fromCallable($stderr);
    }

    /**
     * @return array<string, string>
     */
    public function collect(QuestionHelper $helper, ArgvInput $input): array
    {
        $mapping = [];
        $this->output->write("Enter mappings in 'source=target' format. Enter an empty line to finish.\n");

        while (true) {
            $mappingEntry = $helper->ask($input, $this->output, new Question('> '));
            if ($mappingEntry === null || $mappingEntry === '') {
                return $mapping;
            }

            try {
                [$source, $target] = $this->parser->parseMapEntry((string) $mappingEntry);
                $mapping[$source] = $target;
            } catch (CliUsageException) {
                ($this->stderr)("Invalid format. Use source=target.\n");
            }
        }
    }
}
