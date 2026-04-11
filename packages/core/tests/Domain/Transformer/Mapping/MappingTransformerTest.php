<?php

declare(strict_types=1);

namespace DynamicDataImporter\Tests\Domain\Transformer\Mapping;

use DynamicDataImporter\Domain\Model\Row;
use DynamicDataImporter\Domain\Transformer\Mapping\MappingTransformer;
use PHPUnit\Framework\TestCase;

class MappingTransformerTest extends TestCase
{
    public function testTransformRenamesKeysBasedOnMapping(): void
    {
        $mapping = [
            'old_name' => 'new_name',
            'old_age' => 'new_age',
        ];
        $transformer = new MappingTransformer($mapping);
        $row = new Row(1, ['old_name' => 'Alice', 'old_age' => 30, 'other' => 'stays']);

        $transformedRow = $transformer->transform($row);

        $this->assertEquals([
            'new_name' => 'Alice',
            'new_age' => 30,
            'other' => 'stays',
        ], $transformedRow->data);
    }

    public function testTransformWithEmptyMappingReturnsSameData(): void
    {
        $transformer = new MappingTransformer([]);
        $data = ['name' => 'Alice', 'age' => 30];
        $row = new Row(1, $data);

        $transformedRow = $transformer->transform($row);

        $this->assertEquals($data, $transformedRow->data);
    }

    public function testTransformHeaders(): void
    {
        $mapping = ['old' => 'new'];
        $transformer = new MappingTransformer($mapping);

        $this->assertEquals(['new', 'other'], $transformer->transformHeaders(['old', 'other']));
    }

    public function testTransformSupportsIgnoringColumns(): void
    {
        $transformer = new MappingTransformer([
            'age' => '',
        ]);
        $row = new Row(1, ['name' => 'Alice', 'age' => 30, 'email' => 'alice@example.com']);

        $transformedRow = $transformer->transform($row);

        $this->assertEquals([
            'name' => 'Alice',
            'email' => 'alice@example.com',
        ], $transformedRow->data);
        $this->assertEquals(['name', 'email'], $transformer->transformHeaders(['name', 'age', 'email']));
    }

    public function testTransformThrowsWhenMappingCollidesWithExistingHeader(): void
    {
        $transformer = new MappingTransformer(['first_name' => 'name']);
        $row = new Row(1, ['first_name' => 'Alice', 'name' => 'Original']);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Mapping collision');

        $transformer->transform($row);
    }

    public function testTransformHeadersThrowsWhenMappedHeadersCollide(): void
    {
        $transformer = new MappingTransformer([
            'first_name' => 'name',
            'nickname' => 'name',
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Mapping collision');

        $transformer->transformHeaders(['first_name', 'nickname']);
    }
}
