<?php

declare(strict_types=1);

namespace DynamicDataImporter\Cli\Interaction;

use DynamicDataImporter\Cli\Exception\CliUsageException;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\Question;

final readonly class CliFilePrompter
{
    private CliDelimiterPrompter $delimiterPrompter;

    public function __construct(
        private OutputInterface $output,
        ?CliDelimiterPrompter $delimiterPrompter = null,
    ) {
        $this->delimiterPrompter = $delimiterPrompter ?? new CliDelimiterPrompter($this->output);
    }

    public function askFilePath(QuestionHelper $helper, ArgvInput $input): string
    {
        $fileQuestion = new Question('Enter the path to the file: ');
        $fileQuestion->setValidator(static function ($answer) {
            if (! is_file((string) $answer)) {
                throw new CliUsageException('The file does not exist.');
            }

            return $answer;
        });

        return (string) $helper->ask($input, $this->output, $fileQuestion);
    }

    public function askFileType(QuestionHelper $helper, ArgvInput $input): ?string
    {
        $fileType = $helper->ask(
            $input,
            $this->output,
            new ChoiceQuestion('File type (optional, auto-detect if skipped):', ['auto', 'csv', 'json', 'xml', 'xls', 'xlsx'], 'auto'),
        );

        return $fileType === 'auto' ? null : (string) $fileType;
    }

    public function askDelimiter(QuestionHelper $helper, ArgvInput $input, ?string $fileType, string $filePath): ?string
    {
        return $this->delimiterPrompter->ask($helper, $input, $fileType, $filePath);
    }
}
