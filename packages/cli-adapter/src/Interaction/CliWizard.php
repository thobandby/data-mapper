<?php

declare(strict_types=1);

namespace DynamicDataImporter\Cli\Interaction;

use DynamicDataImporter\Cli\Contract\WizardInterface;
use DynamicDataImporter\Cli\Input\CliOptions;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\ArgvInput;

final readonly class CliWizard implements WizardInterface
{
    private \Closure $stdout;

    public function __construct(
        private CliWizardPrompter $wizardPrompter,
        callable $stdout,
    ) {
        $this->stdout = \Closure::fromCallable($stdout);
    }

    public function run(): CliOptions
    {
        ($this->stdout)("Welcome to the Dynamic Data Importer Wizard!\n\n");

        return $this->buildWizardOptions($this->wizardPrompter->collectSelections(new QuestionHelper(), new ArgvInput()));
    }

    /**
     * @param array{
     *   action: string,
     *   filePath: string,
     *   fileType: ?string,
     *   delimiter: ?string,
     *   mapping: array<string, string>,
     *   executionOptions: array{outputFormat: string, tableName: string, writeOutputPath: ?string, dryRun: bool}
     * } $wizardSelections
     */
    private function buildWizardOptions(array $wizardSelections): CliOptions
    {
        return new CliOptions(
            action: $wizardSelections['action'],
            filePath: $wizardSelections['filePath'],
            fileType: $wizardSelections['fileType'],
            delimiter: $wizardSelections['delimiter'],
            mapping: $wizardSelections['mapping'],
            outputFormat: $wizardSelections['executionOptions']['outputFormat'],
            tableName: $wizardSelections['executionOptions']['tableName'],
            writeOutputPath: $wizardSelections['executionOptions']['writeOutputPath'],
            dryRun: $wizardSelections['executionOptions']['dryRun'],
            verbose: false,
        );
    }
}
