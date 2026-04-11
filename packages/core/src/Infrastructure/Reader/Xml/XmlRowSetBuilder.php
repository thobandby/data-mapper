<?php

declare(strict_types=1);

namespace DynamicDataImporter\Infrastructure\Reader\Xml;

use DynamicDataImporter\Domain\Model\Row;

/** @phpstan-import-type RowData from Row */
final readonly class XmlRowSetBuilder
{
    private XmlHeaderCollector $headerCollector;
    private XmlRowNormalizer $rowNormalizer;
    private XmlRowElementResolver $rowElementResolver;

    public function __construct(
        private XmlElementTransformer $transformer,
    ) {
        $this->headerCollector = new XmlHeaderCollector();
        $this->rowNormalizer = new XmlRowNormalizer();
        $this->rowElementResolver = new XmlRowElementResolver();
    }

    /**
     * @return list<RowData>
     */
    public function rows(\SimpleXMLElement $xml): array
    {
        $rows = [];

        foreach ($this->rowElementResolver->resolve($xml) as $element) {
            $rows[] = $this->transformer->transform($element);
        }

        return $rows;
    }

    /**
     * @param list<RowData> $rows
     *
     * @return list<string>
     */
    public function headers(array $rows): array
    {
        return $this->headerCollector->collect($rows);
    }

    /**
     * @param list<RowData> $rows
     * @param list<string>  $headers
     *
     * @return list<RowData>
     */
    public function normalizeRows(array $rows, array $headers): array
    {
        return $this->rowNormalizer->normalize($rows, $headers);
    }
}
