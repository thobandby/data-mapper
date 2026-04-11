<?php

declare(strict_types=1);

namespace DynamicDataImporter\Infrastructure\Reader\Xml;

use DynamicDataImporter\Domain\Model\Row;
use DynamicDataImporter\Port\Reader\TabularReaderInterface;

/** @phpstan-import-type RowData from Row */
final class XmlReader implements TabularReaderInterface
{
    /** @var list<string> */
    private array $headers = [];

    /** @var list<RowData> */
    private array $data = [];

    private readonly XmlDocumentLoader $documentLoader;
    private readonly XmlRowSetBuilder $rowSetBuilder;

    public function __construct(
        private readonly string $filePath,
    ) {
        $transformer = new XmlElementTransformer();
        $this->documentLoader = new XmlDocumentLoader();
        $this->rowSetBuilder = new XmlRowSetBuilder($transformer);
        $this->loadFile();
    }

    /**
     * @return list<string>
     */
    public function headers(): array
    {
        return $this->headers;
    }

    /**
     * @return iterable<Row>
     */
    public function rows(): iterable
    {
        foreach ($this->data as $index => $rowData) {
            yield new Row($index + 1, $rowData);
        }
    }

    private function loadFile(): void
    {
        $previous = libxml_use_internal_errors(true);

        try {
            $xml = $this->documentLoader->load($this->filePath);
            $rows = $this->rowSetBuilder->rows($xml);

            $this->headers = $this->rowSetBuilder->headers($rows);
            $this->data = $this->rowSetBuilder->normalizeRows($rows, $this->headers);
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }
    }
}
