<?php

declare(strict_types=1);

namespace DynamicDataImporter\Infrastructure\Persistence;

use DynamicDataImporter\Domain\Model\Row;

/**
 * @phpstan-import-type RowValue from Row
 */
final class XmlFieldSerializer
{
    /**
     * @param RowValue $value
     */
    public function serialize(string $name, bool|float|int|string|array|null $value): string
    {
        $content = htmlspecialchars($this->normalizeValue($value), \ENT_XML1 | \ENT_QUOTES, 'UTF-8');

        if ($this->isValidElementName($name)) {
            return sprintf("    <%s>%s</%s>\n", $name, $content, $name);
        }

        return sprintf(
            "    <field name=\"%s\">%s</field>\n",
            htmlspecialchars($name, \ENT_XML1 | \ENT_QUOTES, 'UTF-8'),
            $content,
        );
    }

    /**
     * @param RowValue $value
     */
    private function normalizeValue(bool|float|int|string|array|null $value): string
    {
        if (! is_array($value)) {
            return (string) $value;
        }

        return implode(', ', array_map(static fn ($item): string => (string) $item, $value));
    }

    private function isValidElementName(string $name): bool
    {
        return preg_match('/^[A-Za-z_][A-Za-z0-9_.-]*$/', $name) === 1;
    }
}
