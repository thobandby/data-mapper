<?php

declare(strict_types=1);

namespace DynamicDataImporter\Cli\Infrastructure\Console;

use DynamicDataImporter\Cli\Exception\CliIoException;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Output\StreamOutput;

final class CliConsoleIoFactory
{
    public static function createOutput(?OutputInterface $output): OutputInterface
    {
        if ($output !== null) {
            return $output;
        }

        $stream = fopen('php://stdout', 'w');
        if ($stream === false) {
            throw new CliIoException('Could not open php://stdout for writing.');
        }

        return new StreamOutput($stream);
    }

    /**
     * @return callable(string): void
     */
    public static function createStderr(?callable $stderr): callable
    {
        return $stderr ?? static function (string $message): void {
            fwrite(STDERR, $message);
        };
    }
}
