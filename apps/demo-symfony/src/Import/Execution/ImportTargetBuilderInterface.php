<?php

declare(strict_types=1);

namespace App\Import\Execution;

interface ImportTargetBuilderInterface
{
    public function supports(ImportAdapter $adapter): bool;

    public function build(ImportAdapter $adapter, string $tableName): ImportTarget;
}
