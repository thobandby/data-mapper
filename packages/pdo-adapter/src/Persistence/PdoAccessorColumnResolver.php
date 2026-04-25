<?php

declare(strict_types=1);

namespace DynamicDataImporter\Pdo\Persistence;

final class PdoAccessorColumnResolver
{
    public function resolve(\ReflectionMethod $method): ?string
    {
        if ($method->isStatic() || $method->getNumberOfRequiredParameters() > 0) {
            return null;
        }

        if (! preg_match('/^(get|is|has)(.+)$/', $method->getName(), $matches)) {
            return null;
        }

        $column = $this->normalizeColumnName($matches[2]);

        return $column !== '' ? $column : null;
    }

    private function normalizeColumnName(string $name): string
    {
        $normalized = preg_replace('/(?<!^)[A-Z]/', '_$0', $name);

        return strtolower($normalized ?? '');
    }
}
