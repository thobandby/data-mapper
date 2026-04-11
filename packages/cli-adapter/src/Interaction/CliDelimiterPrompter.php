<?php

declare(strict_types=1);

namespace DynamicDataImporter\Cli\Interaction;

use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;

final readonly class CliDelimiterPrompter
{
    public function __construct(private OutputInterface $output)
    {
    }

    public function ask(QuestionHelper $helper, ArgvInput $input, ?string $fileType, string $filePath): ?string
    {
        if ($fileType !== 'csv' && ! str_ends_with(strtolower($filePath), '.csv')) {
            return null;
        }

        $delimiter = $helper->ask($input, $this->output, new Question('CSV delimiter (optional, defaults to auto-detect): ', ''));

        return $delimiter === '' ? null : (string) $delimiter;
    }
}
