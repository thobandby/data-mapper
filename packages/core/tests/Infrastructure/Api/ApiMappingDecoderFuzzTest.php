<?php

declare(strict_types=1);

namespace DynamicDataImporter\Tests\Infrastructure\Api;

use DynamicDataImporter\Infrastructure\Api\ApiMappingDecoder;
use DynamicDataImporter\Tests\Support\Fuzz\DeterministicFuzzDataFactory;
use DynamicDataImporter\Tests\Support\Fuzz\FuzzSeedSequence;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

#[Group('fuzz')]
final class ApiMappingDecoderFuzzTest extends TestCase
{
    #[DataProvider('seedProvider')]
    public function testGeneratedMappingJsonDecodesToStringMapping(int $seed): void
    {
        $case = (new DeterministicFuzzDataFactory($seed))->stringMappingJsonCase();

        $decoded = (new ApiMappingDecoder())->decode(['mapping' => $case['json']]);

        self::assertSame($case['mapping'], $decoded);
    }

    #[DataProvider('seedProvider')]
    public function testStressMappingJsonDecodesToStringMapping(int $seed): void
    {
        $case = (new DeterministicFuzzDataFactory($seed))->stressStringMappingJsonCase();

        $decoded = (new ApiMappingDecoder())->decode(['mapping' => $case['json']]);

        self::assertSame($case['mapping'], $decoded);
    }

    public function testInvalidMappingPayloadsOnlyProduceControlledErrors(): void
    {
        $decoder = new ApiMappingDecoder();
        $payloads = (new DeterministicFuzzDataFactory(20260411))->invalidMappingJsonPayloads();

        foreach ($payloads as $payload) {
            try {
                $decoder->decode(['mapping' => $payload]);
                self::fail('Expected invalid mapping payload to fail.');
            } catch (\InvalidArgumentException|\JsonException) {
                self::assertTrue(true);
            }
        }
    }

    public static function seedProvider(): iterable
    {
        yield from FuzzSeedSequence::provide();
    }
}
