<?php

declare(strict_types=1);

namespace App\Service;

use DynamicDataImporter\Domain\Exception\ImporterException;
use Symfony\Contracts\Translation\TranslatorInterface;

final readonly class ImportExceptionMessageFormatter
{
    public function __construct(
        private TranslatorInterface $translator,
    ) {
    }

    public function formatForUser(ImporterException $exception): string
    {
        return match ($exception->codeName()) {
            'invalid_csv' => $this->formatInvalidCsvMessage($exception),
            'invalid_json' => $this->trans('import.error.invalid_json'),
            'invalid_xml' => $this->trans('import.error.invalid_xml'),
            'cannot_open_file', 'cannot_read_file', 'file_not_found', 'unreadable_file' => $this->trans('import.error.cannot_read'),
            'unsupported_file_type' => $this->trans('import.error.unsupported_file_type'),
            default => $this->trans('import.error.generic'),
        };
    }

    private function formatInvalidCsvMessage(ImporterException $exception): string
    {
        $context = $exception->context();

        if (
            ($context['reason_code'] ?? null) === 'unexpected_column_count'
            && isset($context['line'], $context['expected_columns'], $context['actual_columns'])
        ) {
            return $this->trans('import.error.invalid_csv_column_count', [
                '%line%' => (string) $context['line'],
                '%expected%' => (string) $context['expected_columns'],
                '%actual%' => (string) $context['actual_columns'],
            ]);
        }

        if (($context['reason_code'] ?? null) === 'unclosed_quoted_field' && isset($context['line'])) {
            return $this->trans('import.error.invalid_csv_unclosed_quote', [
                '%line%' => (string) $context['line'],
            ]);
        }

        return $this->trans('import.error.invalid_csv');
    }

    /**
     * @param array<string, scalar|null> $parameters
     */
    private function trans(string $key, array $parameters = []): string
    {
        return $this->translator->trans($key, $parameters);
    }
}
