<?php

declare(strict_types=1);

namespace DynamicDataImporter\Tests\Infrastructure\Reader\Xml;

use DynamicDataImporter\Domain\Exception\ImporterException;
use DynamicDataImporter\Infrastructure\Reader\Xml\XmlReader;
use DynamicDataImporter\Tests\Support\Fuzz\DeterministicFuzzDataFactory;
use DynamicDataImporter\Tests\Support\Fuzz\FuzzSeedSequence;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

#[Group('fuzz')]
final class XmlReaderFuzzTest extends TestCase
{
    #[DataProvider('seedProvider')]
    public function testGeneratedXmlInputsRemainParsableAndConsistent(int $seed): void
    {
        $case = (new DeterministicFuzzDataFactory($seed))->xmlCase();
        $filePath = $this->writeTempFile('xml_fuzz_', $case['content']);

        try {
            $reader = new XmlReader($filePath);
            $rows = iterator_to_array($reader->rows());

            self::assertSame($case['headers'], $reader->headers());
            self::assertCount(count($case['rows']), $rows);

            foreach ($rows as $index => $row) {
                self::assertSame($case['rows'][$index], $row->data);
            }
        } finally {
            unlink($filePath);
        }
    }

    #[DataProvider('seedProvider')]
    public function testMalformedXmlInputsOnlyProduceDomainErrors(int $seed): void
    {
        $payload = (new DeterministicFuzzDataFactory($seed))->malformedXmlPayload();
        $filePath = $this->writeTempFile('xml_fuzz_invalid_', $payload);

        try {
            try {
                $reader = new XmlReader($filePath);
                iterator_to_array($reader->rows());
                self::assertTrue(true);
            } catch (ImporterException) {
                self::assertTrue(true);
            }
        } finally {
            unlink($filePath);
        }
    }

    #[DataProvider('seedProvider')]
    public function testStressXmlInputsRemainParsableAndConsistent(int $seed): void
    {
        $case = (new DeterministicFuzzDataFactory($seed))->xmlStressCase();
        $filePath = $this->writeTempFile('xml_fuzz_stress_', $case['content']);

        try {
            $reader = new XmlReader($filePath);
            $rows = iterator_to_array($reader->rows());

            self::assertSame($case['headers'], $reader->headers());
            self::assertCount(count($case['rows']), $rows);

            foreach ($rows as $index => $row) {
                self::assertSame($case['rows'][$index], $row->data);
            }
        } finally {
            unlink($filePath);
        }
    }

    public function testAdversarialXmlPayloadsOnlyProduceControlledBehavior(): void
    {
        $payloads = (new DeterministicFuzzDataFactory(20260411))->adversarialXmlPayloads();

        foreach ($payloads as $index => $payload) {
            $filePath = $this->writeTempFile('xml_fuzz_adversarial_', $payload);

            try {
                try {
                    $reader = new XmlReader($filePath);
                    self::assertIsArray($reader->headers(), 'XML payload #' . $index . ' should expose headers.');
                    iterator_to_array($reader->rows());
                } catch (ImporterException) {
                    self::assertTrue(true);
                }
            } finally {
                unlink($filePath);
            }
        }
    }

    public static function seedProvider(): iterable
    {
        yield from FuzzSeedSequence::provide();
    }

    private function writeTempFile(string $prefix, string $content): string
    {
        $filePath = tempnam(sys_get_temp_dir(), $prefix);
        self::assertNotFalse($filePath);
        file_put_contents($filePath, $content);

        return $filePath;
    }
}
