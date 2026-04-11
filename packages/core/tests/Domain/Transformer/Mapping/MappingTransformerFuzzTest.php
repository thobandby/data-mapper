<?php

declare(strict_types=1);

namespace DynamicDataImporter\Tests\Domain\Transformer\Mapping;

use DynamicDataImporter\Domain\Exception\ImporterException;
use DynamicDataImporter\Domain\Model\Row;
use DynamicDataImporter\Domain\Transformer\Mapping\MappingTransformer;
use DynamicDataImporter\Tests\Support\Fuzz\DeterministicFuzzDataFactory;
use DynamicDataImporter\Tests\Support\Fuzz\FuzzSeedSequence;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

#[Group('fuzz')]
final class MappingTransformerFuzzTest extends TestCase
{
    #[DataProvider('seedProvider')]
    public function testGeneratedMappingsTransformRowsAndHeadersConsistently(int $seed): void
    {
        $case = (new DeterministicFuzzDataFactory($seed))->mappingTransformerCase();
        $transformer = new MappingTransformer($case['mapping']);
        $row = new Row(1, $case['rowData']);

        $transformedRow = $transformer->transform($row);

        self::assertSame($case['expectedData'], $transformedRow->data);
        self::assertSame($case['expectedHeaders'], $transformer->transformHeaders($case['headers']));
    }

    #[DataProvider('seedProvider')]
    public function testCollidingMappingsOnlyProduceControlledDomainErrors(int $seed): void
    {
        $case = (new DeterministicFuzzDataFactory($seed))->mappingTransformerCollisionCase();
        $transformer = new MappingTransformer($case['mapping']);

        try {
            $transformer->transform(new Row(1, $case['rowData']));
            self::fail('Expected row mapping collision.');
        } catch (ImporterException $exception) {
            self::assertSame('mapping_collision', $exception->codeName());
        }

        try {
            $transformer->transformHeaders($case['headers']);
            self::fail('Expected header mapping collision.');
        } catch (ImporterException $exception) {
            self::assertSame('mapping_header_collision', $exception->codeName());
        }
    }

    public static function seedProvider(): iterable
    {
        yield from FuzzSeedSequence::provide();
    }
}
