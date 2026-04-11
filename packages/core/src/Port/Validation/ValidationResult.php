<?php

declare(strict_types=1);

namespace DynamicDataImporter\Port\Validation;

final readonly class ValidationResult
{
    /** @param array<string, string> $errors */
    public function __construct(
        public bool $valid,
        public array $errors = [],
    ) {
    }

    public static function ok(): self
    {
        return new self(true, []);
    }

    /** @param array<string, string> $errors */
    public static function fail(array $errors): self
    {
        return new self(false, $errors);
    }
}
