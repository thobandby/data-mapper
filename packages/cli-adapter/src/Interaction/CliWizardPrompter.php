<?php

declare(strict_types=1);

namespace DynamicDataImporter\Cli\Interaction;

use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;

final readonly class CliWizardPrompter
{
    public function __construct(
        private OutputInterface $output,
        private CliFilePrompter $filePrompter,
        private CliMappingPrompter $mappingPrompter,
        private CliExecutionOptionsPrompter $executionOptionsPrompter,
    ) {
    }

    /**
     * @return array{
     *   action: string,
     *   filePath: string,
     *   fileType: ?string,
     *   delimiter: ?string,
     *   mapping: array<string, string>,
     *   executionOptions: array{outputFormat: string, tableName: string, writeOutputPath: ?string, dryRun: bool}
     * }
     */
    public function collectSelections(QuestionHelper $helper, ArgvInput $input): array
    {
        $action = (string) $helper->ask(
            $input,
            $this->output,
            new ChoiceQuestion('What would you like to do?', ['analyze', 'preview', 'execute'], 'analyze'),
        );
        $filePath = $this->filePrompter->askFilePath($helper, $input);
        $fileType = $this->filePrompter->askFileType($helper, $input);

        return [
            'action' => $action,
            'filePath' => $filePath,
            'fileType' => $fileType,
            'delimiter' => $this->filePrompter->askDelimiter($helper, $input, $fileType, $filePath),
            'mapping' => $this->mappingPrompter->prompt($helper, $input, $action),
            'executionOptions' => $this->executionOptionsPrompter->prompt($helper, $input, $this->output, $action),
        ];
    }
}
