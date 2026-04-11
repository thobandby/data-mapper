<?php

declare(strict_types=1);

namespace DynamicDataImporter\Tests\Infrastructure\Persistence;

use DynamicDataImporter\Infrastructure\Persistence\InMemoryPersister;
use PHPUnit\Framework\TestCase;

class InMemoryPersisterTest extends TestCase
{
    public function testPersistAndGetEntities(): void
    {
        $persister = new InMemoryPersister();
        $entity1 = new \stdClass();
        $entity1->id = 1;
        $entity2 = new \stdClass();
        $entity2->id = 2;

        $persister->persist($entity1);
        $persister->persist($entity2);
        $persister->flush();

        $this->assertCount(2, $persister->getEntities());
        $this->assertSame($entity1, $persister->getEntities()[0]);
        $this->assertSame($entity2, $persister->getEntities()[1]);
    }

    public function testClear(): void
    {
        $persister = new InMemoryPersister();
        $persister->persist(new \stdClass());
        $persister->flush();
        $persister->clear();

        $this->assertCount(0, $persister->getEntities());
    }
}
