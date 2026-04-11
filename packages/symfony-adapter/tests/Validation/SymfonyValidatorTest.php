<?php

declare(strict_types=1);

namespace DynamicDataImporter\Tests\Symfony\Validation;

use DynamicDataImporter\Symfony\Validation\SymfonyValidator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Constraints\Collection;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Validation;

class SymfonyValidatorTest extends TestCase
{
    public function testValidationSuccess(): void
    {
        $symfonyValidator = Validation::createValidator();
        $constraints = new Collection(
            fields: [
                'email' => [new NotBlank(), new Email()],
                'name' => [new NotBlank()],
            ],
            allowExtraFields: true,
        );

        $validator = new SymfonyValidator($symfonyValidator, $constraints);

        $result = $validator->validate([
            'email' => 'test@example.com',
            'name' => 'John Doe',
        ]);

        $this->assertTrue($result->valid);
        $this->assertEmpty($result->errors);
    }

    public function testValidationFailure(): void
    {
        $symfonyValidator = Validation::createValidator();
        $constraints = new Collection(
            fields: [
                'email' => [new NotBlank(), new Email()],
                'name' => [new NotBlank()],
            ],
        );

        $validator = new SymfonyValidator($symfonyValidator, $constraints);

        $result = $validator->validate([
            'email' => 'invalid-email',
            'name' => '',
        ]);

        $this->assertFalse($result->valid);
        $this->assertArrayHasKey('email', $result->errors);
        $this->assertArrayHasKey('name', $result->errors);
    }
}
