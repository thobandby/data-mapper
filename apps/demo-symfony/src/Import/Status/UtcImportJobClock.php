<?php

declare(strict_types=1);

namespace App\Import\Status;

final class UtcImportJobClock implements ImportJobClockInterface
{
    public function now(): \DateTimeImmutable
    {
        return new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
    }
}
