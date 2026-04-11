<?php

declare(strict_types=1);

namespace DynamicDataImporter\Cli\Input;

use DynamicDataImporter\Cli\Exception\CliUsageException;

final readonly class CliOptionsInputProcessor
{
    public function __construct(
        private CliOptionTokenParser $tokenParser,
        private CliOptionApplier $optionApplier,
    ) {
    }

    /**
     * @param list<string>                                  $args
     * @param callable(string): array{0: string, 1: string} $parseMapEntry
     *
     * @return array{
     *   file: ?string,
     *   file_type: ?string,
     *   delimiter: ?string,
     *   sample_size: int,
     *   mapping_file: ?string,
     *   mapping_json: ?string,
     *   maps: list<array{0: string, 1: string}>,
     *   output_format: string,
     *   table: string,
     *   format: string,
     *   write_output: ?string,
     *   dry_run: bool,
     *   verbose: bool
     * }|null
     */
    public function consumeArguments(array &$args, callable $parseMapEntry): ?array
    {
        $options = $this->defaultOptions();

        while ($args !== []) {
            $token = (string) \array_shift($args);

            if (! str_starts_with($token, '--')) {
                $this->assignPositionalFile($options, $token);
                continue;
            }

            [$name, $value] = $this->tokenParser->parse($token, $args);
            if ($name === 'help') {
                return null;
            }

            $this->optionApplier->apply($options, $name, $value, $parseMapEntry);
        }

        return $options;
    }

    /**
     * @return array{
     *   file: ?string,
     *   file_type: ?string,
     *   delimiter: ?string,
     *   sample_size: int,
     *   mapping_file: ?string,
     *   mapping_json: ?string,
     *   maps: list<array{0: string, 1: string}>,
     *   output_format: string,
     *   table: string,
     *   format: string,
     *   write_output: ?string,
     *   dry_run: bool,
     *   verbose: bool
     * }
     */
    private function defaultOptions(): array
    {
        return [
            'file' => null,
            'file_type' => null,
            'delimiter' => null,
            'sample_size' => 5,
            'mapping_file' => null,
            'mapping_json' => null,
            'maps' => [],
            'output_format' => 'memory',
            'table' => 'imported_data',
            'format' => 'text',
            'write_output' => null,
            'dry_run' => false,
            'verbose' => false,
        ];
    }

    /**
     * @param array{
     *   file: ?string,
     *   file_type: ?string,
     *   delimiter: ?string,
     *   sample_size: int,
     *   mapping_file: ?string,
     *   mapping_json: ?string,
     *   maps: list<array{0: string, 1: string}>,
     *   output_format: string,
     *   table: string,
     *   format: string,
     *   write_output: ?string,
     *   dry_run: bool,
     *   verbose: bool
     * } $options
     */
    private function assignPositionalFile(array &$options, string $token): void
    {
        if ($options['file'] !== null) {
            throw new CliUsageException(\sprintf('Unexpected argument: %s', $token));
        }

        $options['file'] = $token;
    }
}
