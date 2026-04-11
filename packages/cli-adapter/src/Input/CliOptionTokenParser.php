<?php

declare(strict_types=1);

namespace DynamicDataImporter\Cli\Input;

use DynamicDataImporter\Cli\Exception\CliUsageException;

final class CliOptionTokenParser
{
    /**
     * @param list<string> $remainingArgs
     *
     * @return array{0: string, 1: string}
     */
    public function parse(string $token, array &$remainingArgs): array
    {
        if ($token === '-v') {
            return ['verbose', '1'];
        }

        return $this->parseLongOptionToken(substr($token, 2), $remainingArgs);
    }

    /**
     * @param list<string> $remainingArgs
     *
     * @return array{0: string, 1: string}
     */
    private function parseLongOptionToken(string $token, array &$remainingArgs): array
    {
        $option = $this->matchFlagOption($token);
        if ($option !== null) {
            return $option;
        }

        return $this->parseOptionWithValue($token, $remainingArgs);
    }

    /**
     * @return array{0: string, 1: string}|null
     */
    private function matchFlagOption(string $token): ?array
    {
        return match ($token) {
            'help' => ['help', '1'],
            'dry-run' => ['dry-run', '1'],
            'verbose' => ['verbose', '1'],
            default => null,
        };
    }

    /**
     * @param list<string> $remainingArgs
     *
     * @return array{0: string, 1: string}
     */
    private function parseOptionWithValue(string $token, array &$remainingArgs): array
    {
        $parts = explode('=', $token, 2);
        if (\count($parts) === 2) {
            return [$parts[0], $parts[1]];
        }

        if ($remainingArgs === []) {
            throw new CliUsageException(\sprintf('Option --%s requires a value.', $parts[0]));
        }

        return [$parts[0], (string) \array_shift($remainingArgs)];
    }
}
