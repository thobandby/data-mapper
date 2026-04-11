<?php

declare(strict_types=1);

namespace DynamicDataImporter\Cli\Input;

use DynamicDataImporter\Cli\Input\Mapping\CliMapEntryParser;
use DynamicDataImporter\Cli\Input\Mapping\CliMappingParser;

final class CliOptionsParser
{
    private readonly CliMappingParser $mappingParser;
    private readonly CliActionResolver $actionResolver;
    private readonly CliOptionsInputProcessor $inputProcessor;
    private readonly CliOptionsFactory $optionsFactory;
    private readonly CliMapEntryParser $mapEntryParser;

    public function __construct(
        ?CliMappingParser $mappingParser = null,
        ?CliActionResolver $actionResolver = null,
        ?CliOptionsInputProcessor $inputProcessor = null,
        ?CliOptionsFactory $optionsFactory = null,
        ?CliMapEntryParser $mapEntryParser = null,
    ) {
        $this->mappingParser = $mappingParser ?? new CliMappingParser();
        $optionApplier = new CliOptionApplier();
        $this->actionResolver = $actionResolver ?? new CliActionResolver();
        $this->inputProcessor = $inputProcessor ?? new CliOptionsInputProcessor(new CliOptionTokenParser(), $optionApplier);
        $this->optionsFactory = $optionsFactory ?? new CliOptionsFactory($this->mappingParser, $optionApplier);
        $this->mapEntryParser = $mapEntryParser ?? new CliMapEntryParser();
    }

    /**
     * @param list<string> $argv
     */
    public function parse(array $argv): CliOptions
    {
        $args = $argv;
        \array_shift($args);

        if ($args === []) {
            return new CliOptions('help');
        }

        $action = $this->actionResolver->resolve($args);
        $options = $this->inputProcessor->consumeArguments($args, $this->parseMapEntry(...));

        if ($options === null) {
            return new CliOptions('help');
        }

        return $this->optionsFactory->create($action, $options);
    }

    /**
     * @return array{0: string, 1: string}
     */
    public function parseMapEntry(string $value): array
    {
        return $this->mapEntryParser->parse($value);
    }
}
