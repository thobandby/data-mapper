<?php

declare(strict_types=1);

namespace App\Import\Http;

use Symfony\Component\HttpFoundation\Request;

final class ImportContextResolver
{
    private const string DEFAULT_ADAPTER = 'memory';

    public function resolve(
        Request $request,
        string $tableParam = 'table',
    ): ImportContext {
        return new ImportContext(
            file: $this->getString($request, 'file'),
            adapter: $this->getString(
                $request,
                'adapter',
                self::DEFAULT_ADAPTER
            ),
            fileType: $this->getString($request, 'file_type'),
            tableName: $this->resolveTableName($request, $tableParam),
            delimiter: $this->resolveDelimiter($request),
            mapping: $this->resolveMapping($request),
            targetColumns: $this->resolveTargetColumns($request),
        );
    }

    private function getString(
        Request $request,
        string $key,
        string $default = '',
    ): string {
        $queryValue = $request->query->get($key);
        if ($queryValue !== null) {
            return (string) $queryValue;
        }

        return (string) $request->request->get($key, $default);
    }

    /**
     * @return array<string, string>
     */
    private function resolveMapping(Request $request): array
    {
        /** @var array<string, string> $requestMapping */
        $requestMapping = $request->request->all('mapping');
        if ($requestMapping !== []) {
            return $requestMapping;
        }

        return $request->query->all('mapping');
    }

    /**
     * @return list<string>
     */
    private function resolveTargetColumns(Request $request): array
    {
        /** @var list<string> $requestTargetColumns */
        $requestTargetColumns = array_values(
            $request->request->all('target_columns')
        );
        if ($requestTargetColumns !== []) {
            return $requestTargetColumns;
        }

        return array_values($request->query->all('target_columns'));
    }

    private function resolveDelimiter(Request $request): ?string
    {
        $delimiter = $this->getString(
            $request,
            'delimiter'
        );

        if ($delimiter === '') {
            return null;
        }

        return $delimiter;
    }

    private function resolveTableName(
        Request $request,
        string $paramName,
    ): string {
        $tableName = $this->getString(
            $request,
            $paramName,
            TableNameSanitizer::DEFAULT_TABLE_NAME
        );

        return TableNameSanitizer::sanitize($tableName);
    }
}
