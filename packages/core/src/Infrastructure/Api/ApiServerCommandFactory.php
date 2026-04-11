<?php

declare(strict_types=1);

namespace DynamicDataImporter\Infrastructure\Api;

final class ApiServerCommandFactory
{
    public function resolvePort(mixed $value, int $default = 8000): int
    {
        if ($value === null || $value === '') {
            return $default;
        }

        $port = filter_var($value, \FILTER_VALIDATE_INT, [
            'options' => [
                'min_range' => 1,
                'max_range' => 65535,
            ],
        ]);

        if (! is_int($port)) {
            throw new \InvalidArgumentException('Port must be an integer between 1 and 65535.');
        }

        return $port;
    }

    /**
     * @return list<string>
     */
    public function createCommand(int $port, string $apiIndex): array
    {
        return [
            PHP_BINARY,
            '-S',
            sprintf('0.0.0.0:%d', $port),
            $apiIndex,
        ];
    }
}
