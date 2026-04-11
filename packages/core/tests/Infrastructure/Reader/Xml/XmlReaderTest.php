<?php

declare(strict_types=1);

namespace DynamicDataImporter\Tests\Infrastructure\Reader\Xml;

use DynamicDataImporter\Domain\Exception\ImporterException;
use DynamicDataImporter\Infrastructure\Reader\Xml\XmlReader;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class XmlReaderTest extends TestCase
{
    private string $tempFile;

    protected function setUp(): void
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'xml_test_');
        self::assertNotFalse($tempFile);
        $this->tempFile = $tempFile;
    }

    protected function tearDown(): void
    {
        if (is_file($this->tempFile)) {
            unlink($this->tempFile);
        }
    }

    public function testReadXmlCollectionWithTagOnlyMapping(): void
    {
        file_put_contents($this->tempFile, <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<records>
  <record id="1">
    <name>Alice</name>
    <age>30</age>
  </record>
  <record id="2">
    <name>Bob</name>
    <age>25</age>
    <email>bob@example.com</email>
  </record>
</records>
XML);

        $reader = new XmlReader($this->tempFile);

        self::assertSame(['@id', 'name', 'age', 'email'], $reader->headers());

        $rows = iterator_to_array($reader->rows());
        self::assertCount(2, $rows);
        self::assertSame(['@id' => '1', 'name' => 'Alice', 'age' => '30', 'email' => null], $rows[0]->data);
        self::assertSame(['@id' => '2', 'name' => 'Bob', 'age' => '25', 'email' => 'bob@example.com'], $rows[1]->data);
    }

    public function testReadSingleXmlRecord(): void
    {
        file_put_contents($this->tempFile, <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<record>
  <name>Alice</name>
  <age>30</age>
</record>
XML);

        $reader = new XmlReader($this->tempFile);

        self::assertSame(['name', 'age'], $reader->headers());

        $rows = iterator_to_array($reader->rows());
        self::assertCount(1, $rows);
        self::assertSame(['name' => 'Alice', 'age' => '30'], $rows[0]->data);
    }

    public function testReadValidFixture(): void
    {
        $reader = new XmlReader($this->fixturePath('xml_valid_100.xml'));
        $rows = iterator_to_array($reader->rows());

        self::assertSame(['@id', 'name', 'email', 'price', 'description'], $reader->headers());
        self::assertCount(100, $rows);
        self::assertSame([
            '@id' => '1',
            'name' => 'Erika Musterfrau',
            'email' => 'user1@test.local',
            'price' => '1.37',
            'description' => 'Standard Datensatz',
        ], $rows[0]->data);
    }

    public function testInvalidXmlThrowsException(): void
    {
        file_put_contents($this->tempFile, '<records><record></records>');

        $this->expectException(ImporterException::class);
        $this->expectExceptionMessage('Invalid XML');

        new XmlReader($this->tempFile);
    }

    public function testXmlWithDoctypeDeclarationIsRejected(): void
    {
        file_put_contents($this->tempFile, <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE records [
  <!ENTITY xxe SYSTEM "file:///etc/passwd">
]>
<records>
  <record>
    <name>&xxe;</name>
  </record>
</records>
XML);

        $this->expectException(ImporterException::class);
        $this->expectExceptionMessage('DOCTYPE declarations are not allowed.');

        new XmlReader($this->tempFile);
    }

    public function testXmlWithHarmlessDoctypeDeclarationIsRejected(): void
    {
        file_put_contents($this->tempFile, <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE records>
<records>
  <record>
    <name>Alice</name>
  </record>
</records>
XML);

        $this->expectException(ImporterException::class);
        $this->expectExceptionMessage('DOCTYPE declarations are not allowed.');

        new XmlReader($this->tempFile);
    }

    #[DataProvider('invalidXmlFixtureProvider')]
    public function testInvalidXmlFixturesThrowException(string $fixture, string $messageFragment): void
    {
        $this->expectException(ImporterException::class);
        $this->expectExceptionMessage($messageFragment);

        new XmlReader($this->fixturePath($fixture));
    }

    public static function invalidXmlFixtureProvider(): iterable
    {
        yield 'broken nesting' => ['xml_invalid_broken_nesting.xml', 'Opening and ending tag mismatch'];
        yield 'duplicate attribute' => ['xml_invalid_duplicate_attribute.xml', 'Attribute id redefined'];
        yield 'missing closing tag' => ['xml_invalid_missing_closing_tag.xml', 'Premature end of data in tag record line 2'];
        yield 'unescaped chars' => ['xml_invalid_unescaped_chars.xml', 'Premature end of data in tag dataset line 1'];
    }

    public function testEmptyXmlDocumentProducesNoRows(): void
    {
        file_put_contents($this->tempFile, <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<records />
XML);

        $reader = new XmlReader($this->tempFile);

        self::assertSame([], $reader->headers());
        self::assertSame([], iterator_to_array($reader->rows()));
    }

    public function testReadXmlCollectionPreservesRepeatedChildTags(): void
    {
        file_put_contents($this->tempFile, <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<records>
  <record>
    <tag>a</tag>
    <tag>b</tag>
  </record>
</records>
XML);

        $reader = new XmlReader($this->tempFile);
        $rows = iterator_to_array($reader->rows());

        self::assertSame(['tag'], $reader->headers());
        self::assertCount(1, $rows);
        self::assertSame(['tag' => ['a', 'b']], $rows[0]->data);
    }

    public function testReadXmlCollectionIncludesAttributes(): void
    {
        file_put_contents($this->tempFile, <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<records>
  <record id="1" type="customer">
    <name lang="en">Alice</name>
    <age unit="years">30</age>
  </record>
</records>
XML);

        $reader = new XmlReader($this->tempFile);

        self::assertSame(['@id', '@type', 'name', 'name.@lang', 'age', 'age.@unit'], $reader->headers());

        $rows = iterator_to_array($reader->rows());
        self::assertCount(1, $rows);
        self::assertSame([
            '@id' => '1',
            '@type' => 'customer',
            'name' => 'Alice',
            'name.@lang' => 'en',
            'age' => '30',
            'age.@unit' => 'years',
        ], $rows[0]->data);
    }

    public function testReadXmlCollectionFlattensNestedElementsAndAttributes(): void
    {
        file_put_contents($this->tempFile, <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<records>
  <record id="1">
    <person category="vip">
      <name lang="en">Alice</name>
    </person>
  </record>
</records>
XML);

        $reader = new XmlReader($this->tempFile);
        $rows = iterator_to_array($reader->rows());

        self::assertSame(['@id', 'person.@category', 'person.name', 'person.name.@lang'], $reader->headers());
        self::assertCount(1, $rows);
        self::assertSame([
            '@id' => '1',
            'person.@category' => 'vip',
            'person.name' => 'Alice',
            'person.name.@lang' => 'en',
        ], $rows[0]->data);
    }

    public function testReadXmlCollectionPreservesRepeatedChildAttributes(): void
    {
        file_put_contents($this->tempFile, <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<records>
  <record>
    <tag lang="en">Alpha</tag>
    <tag lang="de">Beta</tag>
  </record>
</records>
XML);

        $reader = new XmlReader($this->tempFile);
        $rows = iterator_to_array($reader->rows());

        self::assertSame(['tag', 'tag.@lang'], $reader->headers());
        self::assertCount(1, $rows);
        self::assertSame([
            'tag' => ['Alpha', 'Beta'],
            'tag.@lang' => ['en', 'de'],
        ], $rows[0]->data);
    }

    private function fixturePath(string $fixture): string
    {
        return dirname(__DIR__, 3) . '/data/' . $fixture;
    }
}
