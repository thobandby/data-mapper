<?php

declare(strict_types=1);

namespace DynamicDataImporter\Tests\Infrastructure\Reader;

use DynamicDataImporter\Domain\Model\Row;
use DynamicDataImporter\Domain\Transformer\TransformerInterface;
use DynamicDataImporter\Infrastructure\Reader\TransformedReader;
use DynamicDataImporter\Port\Reader\TabularReaderInterface;
use PHPUnit\Framework\TestCase;

final class TransformedReaderTest extends TestCase
{
    public function testHeadersAreTransformed(): void
    {
        $reader = $this->createMock(TabularReaderInterface::class);
        $reader->method('headers')->willReturn(['first_name']);

        $transformer = $this->createMock(TransformerInterface::class);
        $transformer->expects($this->once())
            ->method('transformHeaders')
            ->with(['first_name'])
            ->willReturn(['name']);

        $transformedReader = new TransformedReader($reader, $transformer);

        self::assertSame(['name'], $transformedReader->headers());
    }

    public function testRowsAreTransformedInOrder(): void
    {
        $rows = [
            new Row(1, ['first_name' => 'Alice']),
            new Row(2, ['first_name' => 'Bob']),
        ];

        $reader = $this->createMock(TabularReaderInterface::class);
        $reader->method('rows')->willReturn($rows);

        $transformer = $this->createMock(TransformerInterface::class);
        $transformer->expects($this->exactly(2))
            ->method('transform')
            ->willReturnCallback(static fn (Row $row): Row => new Row($row->index, ['name' => $row->data['first_name']]));

        $transformedReader = new TransformedReader($reader, $transformer);
        $transformedRows = iterator_to_array($transformedReader->rows());

        self::assertCount(2, $transformedRows);
        self::assertSame(1, $transformedRows[0]->index);
        self::assertSame(['name' => 'Alice'], $transformedRows[0]->data);
        self::assertSame(2, $transformedRows[1]->index);
        self::assertSame(['name' => 'Bob'], $transformedRows[1]->data);
    }
}
