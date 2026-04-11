<?php

declare(strict_types=1);

namespace DynamicDataImporter\Infrastructure\Reader\Json;

use DynamicDataImporter\Domain\Model\Row;
use DynamicDataImporter\Infrastructure\Reader\FileContentLoader;
use DynamicDataImporter\Port\Reader\TabularReaderInterface;

/** @phpstan-import-type RowData from Row */
final class JsonReader implements TabularReaderInterface
{
    /** @var list<string> */
    private array $headers = [];

    /** @var list<RowData> */
    private array $data = [];

    private readonly FileContentLoader $fileContentLoader;
    private readonly JsonRowDecoder $rowDecoder;

    public function __construct(
        private readonly string $filePath,
    ) {
        $this->fileContentLoader = new FileContentLoader();
        $this->rowDecoder = new JsonRowDecoder();
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
        $content = $this->fileContentLoader->load($this->filePath);

        $this->data = $this->rowDecoder->decode($content);
        $this->headers = $this->extractHeaders($this->data);
    }

    /**
     * @param list<RowData> $data
     *
     * @return list<string>
     */
    private function extractHeaders(array $data): array
    {
        if ($data === []) {
            return [];
        }

        return array_keys(reset($data));
    }
}
