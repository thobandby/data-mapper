<?php

declare(strict_types=1);

namespace App\Import\Status;

interface ImportJobClockInterface
{
    public function now(): \DateTimeImmutable;
}
