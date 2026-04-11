<?php

declare(strict_types=1);

namespace DynamicDataImporter\Tests\Infrastructure\Persistence;

use DynamicDataImporter\Infrastructure\Persistence\XmlPersister;
use PHPUnit\Framework\TestCase;

final class XmlPersisterTest extends TestCase
{
    private string $tempFile;

    protected function setUp(): void
    {
        $this->tempFile = tempnam(sys_get_temp_dir(), 'test_xml_') . '.xml';
    }

    protected function tearDown(): void
    {
        if (file_exists($this->tempFile)) {
            unlink($this->tempFile);
        }
    }

    public function testPersistAndFlushWritesFlatXml(): void
    {
        $persister = new XmlPersister($this->tempFile);

        $entity = new \stdClass();
        $entity->name = 'Alice';
        $entity->age = 30;

        $persister->persist($entity);
        $persister->flush();

        $xml = (string) file_get_contents($this->tempFile);

        self::assertStringContainsString('<?xml version="1.0" encoding="UTF-8"?>', $xml);
        self::assertStringContainsString('<rows>', $xml);
        self::assertStringContainsString('<row>', $xml);
        self::assertStringContainsString('<name>Alice</name>', $xml);
        self::assertStringContainsString('<age>30</age>', $xml);
    }

    public function testPersistFallsBackToFieldElementsForInvalidXmlNames(): void
    {
        $persister = new XmlPersister($this->tempFile);
        $persister->persist((object) ['data' => ['full name' => 'Alice & Bob']]);
        $persister->flush();

        $xml = (string) file_get_contents($this->tempFile);

        self::assertStringContainsString('<field name="full name">Alice &amp; Bob</field>', $xml);
    }
}
