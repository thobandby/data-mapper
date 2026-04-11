<?php

declare(strict_types=1);

namespace DynamicDataImporter\Cli\Application;

use DynamicDataImporter\Application\Service\ImportWorkflowService;
use DynamicDataImporter\Cli\Contract\ArtifactWriterInterface;
use DynamicDataImporter\Cli\Contract\OutputFormatterInterface;
use DynamicDataImporter\Cli\Contract\WizardInterface;
use DynamicDataImporter\Cli\Execution\CliActionExecutor;
use DynamicDataImporter\Cli\Execution\WorkflowExecutor;
use DynamicDataImporter\Cli\Infrastructure\Filesystem\CliArtifactContentBuilder;
use DynamicDataImporter\Cli\Infrastructure\Filesystem\CliArtifactDirectoryEnsurer;
use DynamicDataImporter\Cli\Infrastructure\Filesystem\CliArtifactWriter;
use DynamicDataImporter\Cli\Input\CliActionResolver;
use DynamicDataImporter\Cli\Input\CliOptionApplier;
use DynamicDataImporter\Cli\Input\CliOptionsFactory;
use DynamicDataImporter\Cli\Input\CliOptionsInputProcessor;
use DynamicDataImporter\Cli\Input\CliOptionsParser;
use DynamicDataImporter\Cli\Input\CliOptionTokenParser;
use DynamicDataImporter\Cli\Input\Mapping\CliMapEntryParser;
use DynamicDataImporter\Cli\Input\Mapping\CliMappingArrayValidator;
use DynamicDataImporter\Cli\Input\Mapping\CliMappingFileLoader;
use DynamicDataImporter\Cli\Input\Mapping\CliMappingJsonDecoder;
use DynamicDataImporter\Cli\Input\Mapping\CliMappingParser;
use DynamicDataImporter\Cli\Interaction\CliDelimiterPrompter;
use DynamicDataImporter\Cli\Interaction\CliExecutionOptionsPrompter;
use DynamicDataImporter\Cli\Interaction\CliFilePrompter;
use DynamicDataImporter\Cli\Interaction\CliMappingPrompter;
use DynamicDataImporter\Cli\Interaction\CliWizard;
use DynamicDataImporter\Cli\Interaction\CliWizardPrompter;
use DynamicDataImporter\Cli\Output\CliAnalyzeTextFormatter;
use DynamicDataImporter\Cli\Output\CliDeferredJsonEncoder;
use DynamicDataImporter\Cli\Output\CliExecuteTextFormatter;
use DynamicDataImporter\Cli\Output\CliExecutionLineBuilder;
use DynamicDataImporter\Cli\Output\CliExecutionOutputValueFormatter;
use DynamicDataImporter\Cli\Output\CliJsonValueNormalizer;
use DynamicDataImporter\Cli\Output\CliOutputFormatter;
use DynamicDataImporter\Cli\Output\CliPreviewTextFormatter;
use DynamicDataImporter\Cli\Output\CliSampleTableRenderer;
use DynamicDataImporter\Cli\Output\CliTextPayloadFormatter;
use DynamicDataImporter\Cli\Output\CliUsageFormatter;
use Symfony\Component\Console\Output\OutputInterface;

final class CliApplicationFactory
{
    public function createRunner(
        ImportWorkflowService $workflow,
        OutputInterface $output,
        callable $stdout,
        callable $stderr,
    ): CliApplicationRunner {
        $parser = $this->createParser();
        $formatter = $this->createFormatter($output, $stdout);

        return new CliApplicationRunner(
            $parser,
            new CliRunHandler(
                $this->createWizard($parser, $output, $stdout, $stderr),
                new CliActionExecutor(new WorkflowExecutor($workflow), $output),
                $this->createArtifactWriter($formatter),
                $formatter,
                $stdout,
            ),
            new CliExceptionPresenter($formatter, $stderr),
        );
    }

    public function createFormatter(OutputInterface $output, callable $stdout): OutputFormatterInterface
    {
        $stdoutClosure = \Closure::fromCallable($stdout);
        $jsonEncoder = new CliDeferredJsonEncoder();
        $formatter = new CliOutputFormatter(
            new CliUsageFormatter(),
            $this->createTextPayloadFormatter($output, $stdoutClosure, $jsonEncoder),
            new CliJsonValueNormalizer(),
        );
        $jsonEncoder->bindFormatter($formatter);

        return $formatter;
    }

    private function createTextPayloadFormatter(
        OutputInterface $output,
        \Closure $stdout,
        CliDeferredJsonEncoder $jsonEncoder,
    ): CliTextPayloadFormatter {
        $sampleTableRenderer = new CliSampleTableRenderer($output, $stdout, $jsonEncoder->encode(...));

        return new CliTextPayloadFormatter(
            new CliAnalyzeTextFormatter($stdout, $sampleTableRenderer),
            new CliPreviewTextFormatter($stdout, $sampleTableRenderer, $jsonEncoder->encode(...)),
            new CliExecuteTextFormatter(
                new CliExecutionLineBuilder(),
                new CliExecutionOutputValueFormatter($jsonEncoder->encode(...)),
            ),
        );
    }

    private function createParser(): CliOptionsParser
    {
        $mapEntryParser = new CliMapEntryParser();
        $mappingJsonDecoder = new CliMappingJsonDecoder(new CliMappingArrayValidator());
        $mappingParser = new CliMappingParser(
            new CliMappingFileLoader($mappingJsonDecoder),
            $mappingJsonDecoder,
        );
        $optionApplier = new CliOptionApplier();

        return new CliOptionsParser(
            $mappingParser,
            new CliActionResolver(),
            new CliOptionsInputProcessor(new CliOptionTokenParser(), $optionApplier),
            new CliOptionsFactory($mappingParser, $optionApplier),
            $mapEntryParser,
        );
    }

    private function createWizard(
        CliOptionsParser $parser,
        OutputInterface $output,
        callable $stdout,
        callable $stderr,
    ): WizardInterface {
        $filePrompter = new CliFilePrompter($output, new CliDelimiterPrompter($output));
        $mappingPrompter = new CliMappingPrompter(
            new \DynamicDataImporter\Cli\Input\Mapping\CliMappingEntryCollector($parser, $output, $stderr),
            $output,
        );

        return new CliWizard(
            new CliWizardPrompter(
                $output,
                $filePrompter,
                $mappingPrompter,
                new CliExecutionOptionsPrompter(),
            ),
            $stdout,
        );
    }

    private function createArtifactWriter(OutputFormatterInterface $formatter): ArtifactWriterInterface
    {
        return new CliArtifactWriter(
            new CliArtifactContentBuilder($formatter),
            new CliArtifactDirectoryEnsurer(),
        );
    }
}
