<?php

declare(strict_types=1);

namespace DynamicDataImporter\Cli\Interaction;

use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\Question;

final class CliExecutionOptionsPrompter
{
    /**
     * @return array{outputFormat: string, tableName: string, writeOutputPath: ?string, dryRun: bool}
     */
    public function prompt(QuestionHelper $helper, ArgvInput $input, OutputInterface $output, string $action): array
    {
        $options = $this->defaultOptions();

        if ($action !== 'execute') {
            return $options;
        }

        if ($this->isDryRun($helper, $input, $output)) {
            $options['dryRun'] = true;

            return $options;
        }

        $options['outputFormat'] = $this->askOutputFormat($helper, $input, $output);
        $options['tableName'] = $this->resolveTableName($helper, $input, $output, $options['outputFormat']);
        $options['writeOutputPath'] = $this->askWriteOutputPath($helper, $input, $output);

        return $options;
    }

    /**
     * @return array{outputFormat: string, tableName: string, writeOutputPath: ?string, dryRun: bool}
     */
    private function defaultOptions(): array
    {
        return [
            'outputFormat' => 'memory',
            'tableName' => 'imported_data',
            'writeOutputPath' => null,
            'dryRun' => false,
        ];
    }

    private function isDryRun(QuestionHelper $helper, ArgvInput $input, OutputInterface $output): bool
    {
        return $helper->ask($input, $output, new ChoiceQuestion('Is this a dry run?', ['yes', 'no'], 'no')) === 'yes';
    }

    private function askOutputFormat(QuestionHelper $helper, ArgvInput $input, OutputInterface $output): string
    {
        return (string) $helper->ask(
            $input,
            $output,
            new ChoiceQuestion('Output format:', ['memory', 'json', 'sql'], 'memory'),
        );
    }

    private function resolveTableName(
        QuestionHelper $helper,
        ArgvInput $input,
        OutputInterface $output,
        string $outputFormat,
    ): string {
        if ($outputFormat !== 'sql') {
            return 'imported_data';
        }

        return (string) $helper->ask($input, $output, new Question('SQL table name: ', 'imported_data'));
    }

    private function askWriteOutputPath(QuestionHelper $helper, ArgvInput $input, OutputInterface $output): ?string
    {
        $writeOutputPath = $helper->ask($input, $output, new Question('Path to write output file (optional): ', ''));

        return $writeOutputPath === '' ? null : (string) $writeOutputPath;
    }
}
