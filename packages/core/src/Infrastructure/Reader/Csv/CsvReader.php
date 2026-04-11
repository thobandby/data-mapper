<?php

declare(strict_types=1);

namespace DynamicDataImporter\Infrastructure\Reader\Csv;

use DynamicDataImporter\Domain\Model\Row;
use DynamicDataImporter\Port\Reader\TabularReaderInterface;

final class CsvReader implements TabularReaderInterface
{
    /** @var list<string> */
    private array $headers = [];

    /** @var resource */
    private $handle;
    private readonly CsvDataRowIterator $dataRowIterator;

    /**
     * @throws \Throwable
     */
    public function __construct(
        string $filePath,
        private readonly CsvOptions $options,
    ) {
        $recordParser = new CsvRecordParser($options);
        $recordStream = new CsvRecordStream($recordParser);
        $this->dataRowIterator = new CsvDataRowIterator($recordStream, new CsvRowDataMapper());

        try {
            $this->handle = (new CsvHandleFactory())->open($filePath);
            $headerSet = (new CsvHeaderSetInitializer(
                $recordStream,
                new CsvStructureValidator(),
                $options,
            ))->initialize($this->handle);
            $this->headers = $headerSet['headers'];
        } catch (\Throwable $exception) {
            $this->close();
            throw $exception;
        }
    }

    public function __destruct()
    {
        $this->close();
    }

    /**
     * @return list<string>
     */
    public function headers(): array
    {
        return $this->headers;
    }

    /**
     * @return \Generator<int, Row>
     */
    public function rows(): iterable
    {
        yield from $this->dataRowIterator->iterate($this->handle, $this->headers, $this->options->hasHeader);
    }

    private function close(): void
    {
        if (is_resource($this->handle)) {
            fclose($this->handle);
        }
    }
}
