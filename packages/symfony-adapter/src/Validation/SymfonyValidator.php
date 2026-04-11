<?php

declare(strict_types=1);

namespace DynamicDataImporter\Symfony\Validation;

use DynamicDataImporter\Domain\Model\Row;
use DynamicDataImporter\Port\Validation\ValidationResult;
use DynamicDataImporter\Port\Validation\ValidatorInterface;
use Symfony\Component\Validator\Constraints\Collection;
use Symfony\Component\Validator\Validator\ValidatorInterface as SymfonyValidatorInterface;

/** @phpstan-import-type RowData from Row */
final readonly class SymfonyValidator implements ValidatorInterface
{
    public function __construct(
        private SymfonyValidatorInterface $validator,
        private Collection $constraints,
    ) {
    }

    /** @param RowData $data */
    public function validate(array $data): ValidationResult
    {
        $violations = $this->validator->validate($data, $this->constraints);

        if (count($violations) === 0) {
            return ValidationResult::ok();
        }

        $errors = [];
        foreach ($violations as $violation) {
            $propertyPath = $violation->getPropertyPath();
            $propertyPath = trim($propertyPath, '[]');
            $errors[$propertyPath] = (string) $violation->getMessage();
        }

        return ValidationResult::fail($errors);
    }
}
