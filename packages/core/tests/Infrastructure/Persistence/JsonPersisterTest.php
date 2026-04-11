<?php

declare(strict_types=1);

namespace DynamicDataImporter\Tests\Infrastructure\Persistence;

use DynamicDataImporter\Infrastructure\Persistence\JsonPersister;
use PHPUnit\Framework\TestCase;

class JsonPersisterTest extends TestCase
{
    private string $tempFile;

    protected function setUp(): void
    {
        $this->tempFile = tempnam(sys_get_temp_dir(), 'test_json_') . '.json';
    }

    protected function tearDown(): void
    {
        if (file_exists($this->tempFile)) {
            unlink($this->tempFile);
        }
    }

    public function testPersistAndFlush(): void
    {
        $persister = new JsonPersister($this->tempFile);
        $entity1 = new \stdClass();
        $entity1->name = 'Alice';
        $entity1->age = 30;

        $persister->persist($entity1);
        $persister->flush();

        $this->assertFileExists($this->tempFile);
        $data = json_decode(file_get_contents($this->tempFile), true);
        $this->assertCount(1, $data);
        $this->assertEquals(['name' => 'Alice', 'age' => 30], $data[0]);
    }

    public function testPersistMultipleAndFlush(): void
    {
        $persister = new JsonPersister($this->tempFile);
        $entity1 = (object) ['data' => ['id' => 1]];
        $entity2 = (object) ['data' => ['id' => 2]];

        $persister->persist($entity1);
        $persister->persist($entity2);
        $persister->flush();

        $data = json_decode(file_get_contents($this->tempFile), true);
        $this->assertCount(2, $data);
        $this->assertEquals(['id' => 1], $data[0]);
        $this->assertEquals(['id' => 2], $data[1]);
    }
}
