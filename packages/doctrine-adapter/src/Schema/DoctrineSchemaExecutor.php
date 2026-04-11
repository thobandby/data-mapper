<?php

declare(strict_types=1);

namespace DynamicDataImporter\Doctrine\Schema;

final class DoctrineSchemaExecutor
{
    /**
     * @template T
     *
     * @param \Closure(): T $operation
     *
     * @return T
     */
    public function run(\Closure $operation, \Closure $exceptionFactory): mixed
    {
        try {
            return $operation();
        } catch (\Throwable $exception) {
            throw $exceptionFactory($exception);
        }
    }
}
