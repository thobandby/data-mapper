<?php

declare(strict_types=1);

namespace App\Tests;

use App\Import\Http\UploadRequestError;
use App\Service\ImportManager;
use App\Service\ImportUploadRequestValidator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Validator\Validation;

final class ImportUploadRequestValidatorTest extends TestCase
{
    /** @var list<string> */
    private array $cleanupFiles = [];

    protected function tearDown(): void
    {
        foreach ($this->cleanupFiles as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
    }

    public function testValidateRejectsUnsupportedAdapter(): void
    {
        $validator = $this->createValidator();

        $result = $validator->validate(
            $this->createUploadedFile('sample.csv', 'text/csv'),
            'bogus',
            'csv',
            '',
            'upload.error',
        );

        self::assertInstanceOf(UploadRequestError::class, $result);
        self::assertSame('upload.error.unsupported_adapter', $result->messageKey);
        self::assertSame(['%adapter%' => 'bogus'], $result->parameters);
    }

    public function testValidateRejectsInvalidMappingJsonStructure(): void
    {
        $validator = $this->createValidator();

        $result = $validator->validate(
            $this->createUploadedFile('sample.csv', 'text/csv'),
            'symfony',
            'csv',
            '{"sku":1}',
            'api.error',
        );

        self::assertInstanceOf(UploadRequestError::class, $result);
        self::assertSame('api.error.invalid_mapping_json', $result->messageKey);
    }

    public function testValidateAcceptsXmlFilesDetectedAsPlainText(): void
    {
        $validator = $this->createValidator();

        $result = $validator->validate(
            $this->createUploadedFile('sample.xml', 'text/plain', '<root><item>Alice</item></root>'),
            'xml',
            'xml',
            '',
            'upload.error',
        );

        self::assertNull($result);
    }

    public function testValidateRejectsInvalidMimeTypeForJsonUploads(): void
    {
        $validator = $this->createValidator();

        $result = $validator->validate(
            $this->createUploadedFile('sample.json', 'image/png', "\x89PNG\r\n\x1a\n"),
            'json',
            'json',
            '',
            'upload.error',
        );

        self::assertInstanceOf(UploadRequestError::class, $result);
        self::assertSame('upload.error.invalid_mime', $result->messageKey);
        self::assertSame(['%type%' => 'JSON'], $result->parameters);
    }

    private function createValidator(): ImportUploadRequestValidator
    {
        $manager = new ImportManager();

        return new ImportUploadRequestValidator(Validation::createValidator(), $manager);
    }

    private function createUploadedFile(string $name, string $mimeType, string $contents = "name\nAlice\n"): UploadedFile
    {
        $file = tempnam(sys_get_temp_dir(), 'validator_');
        self::assertNotFalse($file);
        file_put_contents($file, $contents);
        $this->cleanupFiles[] = $file;

        return new UploadedFile($file, $name, $mimeType, null, true);
    }
}
