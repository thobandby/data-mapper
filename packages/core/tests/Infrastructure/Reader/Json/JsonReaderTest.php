<?php

declare(strict_types=1);

namespace DynamicDataImporter\Tests\Infrastructure\Reader\Json;

use DynamicDataImporter\Domain\Exception\ImporterException;
use DynamicDataImporter\Infrastructure\Reader\Json\JsonReader;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class JsonReaderTest extends TestCase
{
    private string $tempFile;

    protected function setUp(): void
    {
        $this->tempFile = tempnam(sys_get_temp_dir(), 'json_test');
    }

    protected function tearDown(): void
    {
        if (file_exists($this->tempFile)) {
            unlink($this->tempFile);
        }
    }

    public function testReadJsonFile(): void
    {
        $data = [
            ['id' => 1, 'name' => 'John'],
            ['id' => 2, 'name' => 'Jane'],
        ];
        file_put_contents($this->tempFile, json_encode($data));

        $reader = new JsonReader($this->tempFile);

        $this->assertEquals(['id', 'name'], $reader->headers());

        $rows = iterator_to_array($reader->rows());
        $this->assertCount(2, $rows);
        $this->assertEquals(1, $rows[0]->index);
        $this->assertEquals(['id' => 1, 'name' => 'John'], $rows[0]->data);
        $this->assertEquals(2, $rows[1]->index);
        $this->assertEquals(['id' => 2, 'name' => 'Jane'], $rows[1]->data);
    }

    public function testReadValidFixture(): void
    {
        $reader = new JsonReader($this->fixturePath('json_valid_100.json'));

        $this->assertSame(['id', 'name', 'email', 'price', 'description'], $reader->headers());

        $rows = iterator_to_array($reader->rows());

        $this->assertCount(100, $rows);
        $this->assertSame([
            'id' => 1,
            'name' => 'Erika Musterfrau',
            'email' => 'user1@test.local',
            'price' => 1.37,
            'description' => 'Standard Datensatz',
        ], $rows[0]->data);
        $this->assertSame('Zweiter Name', iterator_to_array((new JsonReader($this->fixturePath('json_problem_duplicate_keys.json')))->rows())[0]->data['name']);
    }

    public function testInvalidJsonThrowsException(): void
    {
        file_put_contents($this->tempFile, '{invalid: json}');

        $this->expectException(ImporterException::class);
        $this->expectExceptionMessage('Invalid JSON');

        new JsonReader($this->tempFile);
    }

    #[DataProvider('invalidJsonFixtureProvider')]
    public function testInvalidJsonFixturesThrowException(string $fixture, string $messageFragment): void
    {
        $this->expectException(ImporterException::class);
        $this->expectExceptionMessage($messageFragment);

        new JsonReader($this->fixturePath($fixture));
    }

    public static function invalidJsonFixtureProvider(): iterable
    {
        yield 'missing comma' => ['json_invalid_missing_comma.json', 'Invalid JSON: Syntax error'];
        yield 'trailing comma' => ['json_invalid_trailing_comma.json', 'Invalid JSON: Syntax error'];
        yield 'truncated' => ['json_invalid_truncated.json', 'Invalid JSON: Control character error'];
        yield 'unquoted key nan' => ['json_invalid_unquoted_key_nan.json', 'Invalid JSON: Syntax error'];
    }

    public function testNonArrayJsonThrowsException(): void
    {
        file_put_contents($this->tempFile, json_encode('not an array'));

        $this->expectException(ImporterException::class);
        $this->expectExceptionMessage('JSON must be an array of objects');

        new JsonReader($this->tempFile);
    }

    public function testEmptyJsonFile(): void
    {
        file_put_contents($this->tempFile, json_encode([]));

        $reader = new JsonReader($this->tempFile);

        $this->assertEquals([], $reader->headers());
        $this->assertEmpty(iterator_to_array($reader->rows()));
    }

    public function testScalarItemsInJsonArrayThrowException(): void
    {
        file_put_contents($this->tempFile, json_encode([1, 2]));

        $this->expectException(ImporterException::class);
        $this->expectExceptionMessage('JSON rows must be objects');

        new JsonReader($this->tempFile);
    }

    private function fixturePath(string $fixture): string
    {
        return dirname(__DIR__, 3) . '/data/' . $fixture;
    }
}
