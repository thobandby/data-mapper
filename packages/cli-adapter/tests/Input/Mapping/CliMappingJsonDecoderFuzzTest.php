<?php

declare(strict_types=1);

namespace DynamicDataImporter\Cli\Tests\Input\Mapping;

use DynamicDataImporter\Cli\Exception\CliUsageException;
use DynamicDataImporter\Cli\Input\Mapping\CliMappingJsonDecoder;
use DynamicDataImporter\Tests\Support\Fuzz\DeterministicFuzzDataFactory;
use DynamicDataImporter\Tests\Support\Fuzz\FuzzSeedSequence;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

#[Group('fuzz')]
final class CliMappingJsonDecoderFuzzTest extends TestCase
{
    #[DataProvider('seedProvider')]
    public function testGeneratedMappingJsonDecodesToStringMapping(int $seed): void
    {
        $case = (new DeterministicFuzzDataFactory($seed))->stringMappingJsonCase();

        $decoded = (new CliMappingJsonDecoder())->decode($case['json'], '--mapping-json');

        self::assertSame($case['mapping'], $decoded);
    }

    #[DataProvider('seedProvider')]
    public function testStressMappingJsonDecodesToStringMapping(int $seed): void
    {
        $case = (new DeterministicFuzzDataFactory($seed))->stressStringMappingJsonCase();

        $decoded = (new CliMappingJsonDecoder())->decode($case['json'], '--mapping-json');

        self::assertSame($case['mapping'], $decoded);
    }

    public function testInvalidMappingPayloadsOnlyProduceControlledBehavior(): void
    {
        $decoder = new CliMappingJsonDecoder();
        $payloads = (new DeterministicFuzzDataFactory(20260411))->invalidMappingJsonPayloads();

        foreach ($payloads as $payload) {
            try {
                $decoded = $decoder->decode($payload, '--mapping-json');
                self::assertIsArray($decoded);
            } catch (CliUsageException) {
                self::assertTrue(true);
            }
        }
    }

    public static function seedProvider(): iterable
    {
        yield from FuzzSeedSequence::provide();
    }
}
