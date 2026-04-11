<?php

declare(strict_types=1);

namespace DynamicDataImporter\Infrastructure\Reader\Xml;

use DynamicDataImporter\Domain\Model\Row;

/**
 * @phpstan-import-type RowData from Row
 */
final readonly class XmlElementTransformer
{
    private XmlElementFlattener $flattener;

    public function __construct(
        string $attributePrefix = '@',
        string $pathSeparator = '.',
        string $textSuffix = '#text',
    ) {
        $this->flattener = new XmlElementFlattener(
            new XmlPathHelper($attributePrefix, $pathSeparator),
            new XmlRowValueAppender(),
            $pathSeparator,
            $textSuffix,
        );
    }

    /** @return RowData */
    public function transform(\SimpleXMLElement $element): array
    {
        $row = [];

        foreach ($element->attributes() as $name => $value) {
            $row['@' . $name] = trim((string) $value);
        }

        foreach ($element->children() as $child) {
            $this->flattener->append($row, $child);
        }

        if ($row !== []) {
            return $row;
        }

        return ['value' => trim((string) $element)];
    }
}
