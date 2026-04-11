<?php

declare(strict_types=1);

namespace DynamicDataImporter\Cli\Interaction;

use DynamicDataImporter\Cli\Input\Mapping\CliMappingEntryCollector;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;

final readonly class CliMappingPrompter
{
    public function __construct(
        private CliMappingEntryCollector $entryCollector,
        private OutputInterface $output,
    ) {
    }

    /**
     * @return array<string, string>
     */
    public function prompt(QuestionHelper $helper, ArgvInput $input, string $action): array
    {
        if (! \in_array($action, ['preview', 'execute'], true)) {
            return [];
        }

        if ($helper->ask($input, $this->output, new ChoiceQuestion('Do you want to add a field mapping?', ['no', 'yes'], 'no')) !== 'yes') {
            return [];
        }

        return $this->entryCollector->collect($helper, $input);
    }
}
