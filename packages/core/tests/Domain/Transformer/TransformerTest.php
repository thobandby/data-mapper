<?php

declare(strict_types=1);

namespace DynamicDataImporter\Tests\Domain\Transformer;

use DynamicDataImporter\Domain\Model\Row;
use DynamicDataImporter\Domain\Transformer\Callback\CallbackTransformer;
use DynamicDataImporter\Domain\Transformer\ChainTransformer;
use DynamicDataImporter\Infrastructure\Reader\TransformedReader;
use DynamicDataImporter\Port\Reader\TabularReaderInterface;
use PHPUnit\Framework\TestCase;

class TransformerTest extends TestCase
{
    public function testTransformDataByCustomerChoice(): void
    {
        $row = new Row(1, ['name' => 'John Doe', 'email' => 'john@example.com']);

        $transformer = new CallbackTransformer(function (Row $row) {
            $data = $row->data;
            if (isset($data['name'])) {
                $data['name'] = strtoupper($data['name']);
            }

            return new Row($row->index, $data);
        });

        $transformedRow = $transformer->transform($row);

        $this->assertEquals('JOHN DOE', $transformedRow->data['name']);
        $this->assertEquals('john@example.com', $transformedRow->data['email']);
    }

    public function testChainTransformer(): void
    {
        $row = new Row(1, ['name' => 'John Doe', 'email' => 'john@example.com']);

        $transformer1 = new CallbackTransformer(fn (Row $r) => new Row($r->index, array_merge($r->data, ['name' => strtoupper($r->data['name'])])));
        $transformer2 = new CallbackTransformer(fn (Row $r) => new Row($r->index, array_merge($r->data, ['email' => str_replace('@', '(at)', $r->data['email'])])));

        $chain = new ChainTransformer($transformer1, $transformer2);
        $transformedRow = $chain->transform($row);

        $this->assertEquals('JOHN DOE', $transformedRow->data['name']);
        $this->assertEquals('john(at)example.com', $transformedRow->data['email']);
    }

    public function testTransformedReader(): void
    {
        $mockReader = $this->createMock(TabularReaderInterface::class);
        $mockReader->method('rows')->willReturn([
            new Row(1, ['val' => 1]),
            new Row(2, ['val' => 2]),
        ]);

        $transformer = new CallbackTransformer(fn (Row $r) => new Row($r->index, ['val' => $r->data['val'] * 10]));

        $transformedReader = new TransformedReader($mockReader, $transformer);

        $rows = iterator_to_array($transformedReader->rows());

        $this->assertCount(2, $rows);
        $this->assertEquals(10, $rows[0]->data['val']);
        $this->assertEquals(20, $rows[1]->data['val']);
    }
}
