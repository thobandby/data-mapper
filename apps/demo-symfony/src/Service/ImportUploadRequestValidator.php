<?php

declare(strict_types=1);

namespace App\Service;

use App\Import\Execution\ImportTargetFactory;
use App\Import\Http\UploadRequestError;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final readonly class ImportUploadRequestValidator
{
    private const string MAPPING_JSON_SUFFIX = '.invalid_mapping_json';
    private const string PLAIN_TEXT_MIME = 'text/plain';

    public function __construct(
        private ValidatorInterface $validator,
        private ImportManager $importManager,
    ) {
    }

    public function validate(
        UploadedFile $file,
        string $normalizedAdapter,
        string $fileType,
        string $mappingJson,
        string $messagePrefix,
    ): ?UploadRequestError {
        $checks = [
            $this->validateAdapter($normalizedAdapter, $messagePrefix),
            $this->validateFileType($fileType, $messagePrefix),
            $this->validateFileSize($file, $messagePrefix),
            $this->validateMimeType($file, $fileType, $messagePrefix),
            $this->validateMappingJson($mappingJson, $messagePrefix),
        ];

        foreach ($checks as $check) {
            if ($check instanceof UploadRequestError) {
                return $check;
            }
        }

        return null;
    }

    private function validateAdapter(string $normalizedAdapter, string $messagePrefix): ?UploadRequestError
    {
        $violations = $this->validator->validate($normalizedAdapter, [
            new Assert\Choice(
                choices: ImportTargetFactory::supportedAdapters(),
                message: $messagePrefix . '.unsupported_adapter',
            ),
        ]);

        return count($violations) > 0
            ? new UploadRequestError($messagePrefix . '.unsupported_adapter', ['%adapter%' => $normalizedAdapter])
            : null;
    }

    private function validateFileType(string $fileType, string $messagePrefix): ?UploadRequestError
    {
        $violations = $this->validator->validate(strtolower($fileType), [
            new Assert\Choice(
                choices: ['csv', 'xlsx', 'xls', 'json', 'xml'],
                message: $messagePrefix . '.unsupported_file_type',
            ),
        ]);

        return count($violations) > 0
            ? new UploadRequestError($messagePrefix . '.unsupported_file_type', ['%type%' => $fileType])
            : null;
    }

    private function validateFileSize(UploadedFile $file, string $messagePrefix): ?UploadRequestError
    {
        $violations = $this->validator->validate($file, [
            new Assert\File(
                maxSize: (string) $this->importManager->maxUploadBytes(),
                maxSizeMessage: $messagePrefix . '.file_too_large',
            ),
        ]);

        return count($violations) > 0
            ? new UploadRequestError($messagePrefix . '.file_too_large', ['%size%' => (string) $this->importManager->maxUploadMegabytes()])
            : null;
    }

    private function validateMimeType(UploadedFile $file, string $fileType, string $messagePrefix): ?UploadRequestError
    {
        $mimeTypes = $this->allowedMimeTypes($fileType);
        if ($mimeTypes === []) {
            return null;
        }

        $violations = $this->validator->validate($file, [
            new Assert\File(
                mimeTypes: $mimeTypes,
                mimeTypesMessage: $messagePrefix . '.invalid_mime',
            ),
        ]);

        return count($violations) > 0
            ? new UploadRequestError($messagePrefix . '.invalid_mime', ['%type%' => strtoupper($fileType)])
            : null;
    }

    private function validateMappingJson(string $mappingJson, string $messagePrefix): ?UploadRequestError
    {
        $messageKey = $messagePrefix . self::MAPPING_JSON_SUFFIX;
        $violations = $this->validator->validate($mappingJson, [
            new Assert\Callback(function (mixed $value, ExecutionContextInterface $context) use ($messageKey): void {
                if (is_string($value) && $value !== '' && ! $this->isValidMappingJson($value)) {
                    $context->buildViolation($messageKey)->addViolation();
                }
            }),
        ]);

        return count($violations) > 0 ? new UploadRequestError($messageKey) : null;
    }

    private function isValidMappingJson(string $mappingJson): bool
    {
        $decoded = $this->decodeMappingJson($mappingJson);

        return is_array($decoded) && $this->isStringMapping($decoded);
    }

    /**
     * @return array<mixed>|null
     */
    private function decodeMappingJson(string $mappingJson): ?array
    {
        try {
            $decoded = json_decode($mappingJson, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return null;
        }

        return is_array($decoded) ? $decoded : null;
    }

    /**
     * @param array<mixed> $mapping
     */
    private function isStringMapping(array $mapping): bool
    {
        foreach ($mapping as $source => $target) {
            if (! is_string($source) || ! is_string($target)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return list<string>
     */
    private function allowedMimeTypes(string $fileType): array
    {
        return match (strtolower($fileType)) {
            'csv' => [
                'text/csv',
                self::PLAIN_TEXT_MIME,
                'application/csv',
                'text/x-csv',
                'application/vnd.ms-excel',
            ],
            'json' => [
                'application/json',
                'text/json',
                self::PLAIN_TEXT_MIME,
            ],
            'xml' => [
                'application/xml',
                'text/xml',
                'application/xhtml+xml',
                self::PLAIN_TEXT_MIME,
            ],
            'xls' => [
                'application/vnd.ms-excel',
                'application/octet-stream',
            ],
            'xlsx' => [
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'application/zip',
                'application/octet-stream',
            ],
            default => [],
        };
    }
}
