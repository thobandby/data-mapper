<?php

declare(strict_types=1);

namespace DynamicDataImporter\Cli\Tests\Input;

use DynamicDataImporter\Cli\Exception\CliUsageException;
use DynamicDataImporter\Cli\Input\CliOptionsParser;
use PHPUnit\Framework\TestCase;

final class CliOptionsParserTest extends TestCase
{
    private array $cleanupFiles = [];

    protected function tearDown(): void
    {
        foreach ($this->cleanupFiles as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
    }

    public function testParseBuildsMergedMappingAndNormalizesDelimiter(): void
    {
        $mappingFile = tempnam(sys_get_temp_dir(), 'cli_mapping_');
        self::assertNotFalse($mappingFile);
        $this->cleanupFiles[] = $mappingFile;
        file_put_contents($mappingFile, json_encode([
            'first_name' => 'given_name',
            'age' => 'years',
        ], JSON_THROW_ON_ERROR));

        $options = (new CliOptionsParser())->parse([
            'import',
            'execute',
            '--file',
            'input.csv',
            '--delimiter',
            '\t',
            '--mapping-file',
            $mappingFile,
            '--mapping-json',
            '{"age":"current_age","city":"town"}',
            '--map',
            'city=location',
            '--output-format',
            'sql',
            '--table',
            'users',
            '--format',
            'json',
        ]);

        self::assertSame('execute', $options->action);
        self::assertSame('input.csv', $options->filePath);
        self::assertSame("\t", $options->delimiter);
        self::assertSame([
            'first_name' => 'given_name',
            'age' => 'current_age',
            'city' => 'location',
        ], $options->mapping);
        self::assertSame('sql', $options->outputFormat);
        self::assertSame('users', $options->tableName);
        self::assertSame('json', $options->responseFormat);
    }

    public function testParseRejectsMissingFileForActiveCommands(): void
    {
        $this->expectException(CliUsageException::class);
        $this->expectExceptionMessage('Option --file is required for this action.');

        (new CliOptionsParser())->parse(['import', 'preview']);
    }

    public function testParseReturnsHelpWhenNoArgumentsAreProvided(): void
    {
        $options = (new CliOptionsParser())->parse(['import']);

        self::assertSame('help', $options->action);
    }

    public function testParseRejectsUnsupportedAction(): void
    {
        $this->expectException(CliUsageException::class);
        $this->expectExceptionMessage('Unsupported action: destroy');

        (new CliOptionsParser())->parse(['import', 'destroy']);
    }

    public function testParseRejectsUnexpectedSecondPositionalArgument(): void
    {
        $this->expectException(CliUsageException::class);
        $this->expectExceptionMessage('Unexpected argument: second.csv');

        (new CliOptionsParser())->parse(['import', 'analyze', 'first.csv', 'second.csv']);
    }

    public function testParseRejectsMissingOptionValue(): void
    {
        $this->expectException(CliUsageException::class);
        $this->expectExceptionMessage('Option --delimiter requires a value.');

        (new CliOptionsParser())->parse(['import', 'analyze', '--file', 'input.csv', '--delimiter']);
    }

    public function testParseRejectsInvalidSampleSize(): void
    {
        $this->expectException(CliUsageException::class);
        $this->expectExceptionMessage('Option --sample-size must be a non-negative integer.');

        (new CliOptionsParser())->parse(['import', 'analyze', '--file', 'input.csv', '--sample-size', '-1']);
    }

    public function testParseRejectsInvalidOutputFormat(): void
    {
        $this->expectException(CliUsageException::class);
        $this->expectExceptionMessage('Option --output-format must be one of: memory, json, sql.');

        (new CliOptionsParser())->parse(['import', 'execute', '--file', 'input.csv', '--output-format', 'yaml']);
    }

    public function testParseRejectsInvalidResponseFormat(): void
    {
        $this->expectException(CliUsageException::class);
        $this->expectExceptionMessage('Option --format must be one of: text, json.');

        (new CliOptionsParser())->parse(['import', 'analyze', '--file', 'input.csv', '--format', 'xml']);
    }

    public function testParseRejectsInvalidMapEntry(): void
    {
        $this->expectException(CliUsageException::class);
        $this->expectExceptionMessage('Option --map must use the form source=target.');

        (new CliOptionsParser())->parse(['import', 'preview', '--file', 'input.csv', '--map', 'name']);
    }

    public function testParseRejectsUnreadableMappingFile(): void
    {
        $this->expectException(CliUsageException::class);
        $this->expectExceptionMessage('Mapping file "/tmp/does-not-exist.json" was not found or is not readable.');

        (new CliOptionsParser())->parse([
            'import',
            'preview',
            '--file',
            'input.csv',
            '--mapping-file',
            '/tmp/does-not-exist.json',
        ]);
    }

    public function testParseRejectsInvalidMappingJson(): void
    {
        $this->expectException(CliUsageException::class);
        $this->expectExceptionMessage('--mapping-json must contain a valid JSON object.');

        (new CliOptionsParser())->parse([
            'import',
            'preview',
            '--file',
            'input.csv',
            '--mapping-json',
            '{invalid}',
        ]);
    }

    public function testParseRejectsNonObjectMappingJson(): void
    {
        $this->expectException(CliUsageException::class);
        $this->expectExceptionMessage('--mapping-json must decode to a JSON object.');

        (new CliOptionsParser())->parse([
            'import',
            'preview',
            '--file',
            'input.csv',
            '--mapping-json',
            '"name"',
        ]);
    }

    public function testParseRejectsNonStringMappingValues(): void
    {
        $this->expectException(CliUsageException::class);
        $this->expectExceptionMessage('--mapping-json must contain only string-to-string mappings.');

        (new CliOptionsParser())->parse([
            'import',
            'preview',
            '--file',
            'input.csv',
            '--mapping-json',
            '{"name":1}',
        ]);
    }
}
