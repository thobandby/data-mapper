<?php

declare(strict_types=1);

namespace DynamicDataImporter\Cli\Tests\Application;

use DynamicDataImporter\Cli\Application\CliApplication;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Output\BufferedOutput;

final class CliApplicationTest extends TestCase
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

    public function testAnalyzeActionPrintsHeadersAndSample(): void
    {
        $file = $this->createCsv("first_name,last_name\nAda,Lovelace\n");
        $output = new BufferedOutput();
        $stderr = '';

        $exitCode = (new CliApplication(
            stderr: static function (string $message) use (&$stderr): void {
                $stderr .= $message;
            },
            output: $output,
        ))->run(['import', 'analyze', '--file', $file]);

        $stdout = $output->fetch();

        self::assertSame(0, $exitCode);
        self::assertSame('', $stderr);
        self::assertStringContainsString('Headers: first_name, last_name', $stdout);
        self::assertStringContainsString('Ada', $stdout);
        self::assertStringContainsString('Lovelace', $stdout);
    }

    public function testExecuteActionCanWriteJsonOutputFile(): void
    {
        $file = $this->createCsv("first_name,last_name\nAda,Lovelace\n");
        $outputFile = sys_get_temp_dir() . '/cli_import_' . uniqid('', true) . '.json';
        $this->cleanupFiles[] = $outputFile;
        $output = new BufferedOutput();

        $exitCode = (new CliApplication(
            stderr: static function (): void {
            },
            output: $output,
        ))->run([
            'import',
            'execute',
            '--file',
            $file,
            '--map',
            'first_name=given_name',
            '--output-format',
            'json',
            '--write-output',
            $outputFile,
        ]);

        $stdout = $output->fetch();

        self::assertSame(0, $exitCode);
        self::assertFileExists($outputFile);
        self::assertStringContainsString('Output written to: ' . $outputFile, $stdout);
        self::assertStringContainsString('"given_name": "Ada"', (string) file_get_contents($outputFile));
    }

    public function testHelpActionPrintsUsage(): void
    {
        $output = new BufferedOutput();
        $stderr = '';

        $exitCode = (new CliApplication(
            stderr: static function (string $message) use (&$stderr): void {
                $stderr .= $message;
            },
            output: $output,
        ))->run(['import', 'help']);

        $stdout = $output->fetch();

        self::assertSame(0, $exitCode);
        self::assertSame('', $stderr);
        self::assertStringContainsString('Usage:', $stdout);
        self::assertStringContainsString('import <action> --file [path] [options]', $stdout);
    }

    public function testUsageErrorsReturnExitCodeTwoAndPrintUsage(): void
    {
        $stdout = '';
        $stderr = '';

        $exitCode = (new CliApplication(
            stderr: static function (string $message) use (&$stderr): void {
                $stderr .= $message;
            },
        ))->run(['import', 'execute']);

        self::assertSame(2, $exitCode);
        self::assertSame('', $stdout);
        self::assertStringContainsString('Usage error:', $stderr);
        self::assertStringContainsString('Option --file is required for this action.', $stderr);
        self::assertStringContainsString('Usage:', $stderr);
    }

    public function testExecuteActionFormatsSqlOutputAndCreatesNestedArtifactDirectory(): void
    {
        $file = $this->createCsv("name\nAda\n");
        $output = new BufferedOutput();
        $artifactDirectory = sys_get_temp_dir() . '/cli-artifacts-' . uniqid('', true) . '/nested';
        $outputFile = $artifactDirectory . '/import.sql';
        $this->cleanupFiles[] = $outputFile;

        $exitCode = (new CliApplication(
            stderr: static function (): void {
            },
            output: $output,
        ))->run([
            'import',
            'execute',
            '--file',
            $file,
            '--output-format',
            'sql',
            '--table',
            'users',
            '--write-output',
            $outputFile,
        ]);

        $stdout = $output->fetch();

        self::assertSame(0, $exitCode);
        self::assertFileExists($outputFile);
        self::assertStringContainsString('Output format: sql', $stdout);
        self::assertStringContainsString('Output written to: ' . $outputFile, $stdout);
        self::assertStringContainsString('INSERT INTO "users"', $stdout);
    }

    public function testRunReturnsExitCodeOneWhenWorkflowFails(): void
    {
        $stdout = '';
        $stderr = '';

        $exitCode = (new CliApplication(
            stderr: static function (string $message) use (&$stderr): void {
                $stderr .= $message;
            },
        ))->run([
            'import',
            'preview',
            '--file',
            '/tmp/file-does-not-exist.csv',
        ]);

        self::assertSame(1, $exitCode);
        self::assertSame('', $stdout);
        self::assertStringContainsString('Error: Could not open file.', $stderr);
    }

    private function createCsv(string $contents): string
    {
        $baseFile = tempnam(sys_get_temp_dir(), 'cli_input_');
        self::assertNotFalse($baseFile);
        $file = $baseFile . '.csv';
        rename($baseFile, $file);
        $this->cleanupFiles[] = $file;
        file_put_contents($file, $contents);

        return $file;
    }
}
