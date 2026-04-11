<?php

declare(strict_types=1);

namespace DynamicDataImporter\Infrastructure\Reader\Xml;

use DynamicDataImporter\Domain\Model\Row;

/** @phpstan-import-type RowData from Row */
final readonly class XmlElementFlattener
{
    public function __construct(
        private XmlPathHelper $pathHelper,
        private XmlRowValueAppender $valueAppender,
        private string $pathSeparator,
        private string $textSuffix,
    ) {
    }

    /** @param RowData $row */
    public function append(array &$row, \SimpleXMLElement $element, string $path = ''): void
    {
        $key = $this->pathHelper->elementPath($path, $element->getName());
        $children = iterator_to_array($element->children());
        $text = trim((string) $element);

        if ($children === []) {
            $this->valueAppender->append($row, $key, $text);
            $this->appendAttributes($row, $element, $key);

            return;
        }

        if ($text !== '') {
            $this->valueAppender->append($row, $key . $this->pathSeparator . $this->textSuffix, $text);
        }

        $this->appendAttributes($row, $element, $key);

        foreach ($children as $child) {
            $this->append($row, $child, $key);
        }
    }

    /** @param RowData $row */
    private function appendAttributes(array &$row, \SimpleXMLElement $element, string $path = ''): void
    {
        foreach ($element->attributes() as $name => $value) {
            $this->valueAppender->append(
                $row,
                $this->pathHelper->attributePath($path, (string) $name),
                trim((string) $value),
            );
        }
    }
}
