<?php

declare(strict_types=1);

namespace DynamicDataImporter\Tests\Infrastructure\Reader\Xml;

use DynamicDataImporter\Infrastructure\Reader\Xml\XmlElementTransformer;
use PHPUnit\Framework\TestCase;

final class XmlElementTransformerTest extends TestCase
{
    public function testTransformIncludesRootAndChildAttributes(): void
    {
        $element = new \SimpleXMLElement(<<<'XML'
<record id="1" type="customer">
  <name lang="en">Alice</name>
  <age unit="years">30</age>
</record>
XML);

        $result = (new XmlElementTransformer())->transform($element);

        self::assertSame([
            '@id' => '1',
            '@type' => 'customer',
            'name' => 'Alice',
            'name.@lang' => 'en',
            'age' => '30',
            'age.@unit' => 'years',
        ], $result);
    }

    public function testTransformFlattensNestedElements(): void
    {
        $element = new \SimpleXMLElement(<<<'XML'
<record id="1">
  <person category="vip">
    <name lang="en">Alice</name>
  </person>
</record>
XML);

        $result = (new XmlElementTransformer())->transform($element);

        self::assertSame([
            '@id' => '1',
            'person.@category' => 'vip',
            'person.name' => 'Alice',
            'person.name.@lang' => 'en',
        ], $result);
    }

    public function testTransformAggregatesRepeatedValuesAndAttributes(): void
    {
        $element = new \SimpleXMLElement(<<<'XML'
<record>
  <tag lang="en">Alpha</tag>
  <tag lang="de">Beta</tag>
</record>
XML);

        $result = (new XmlElementTransformer())->transform($element);

        self::assertSame([
            'tag' => ['Alpha', 'Beta'],
            'tag.@lang' => ['en', 'de'],
        ], $result);
    }
}
