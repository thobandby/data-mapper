<?php

declare(strict_types=1);

namespace App\Tests;

use App\Entity\ImportedRow;
use App\Mapping\GenericEntityMapper;
use DynamicDataImporter\Domain\Model\Row;
use PHPUnit\Framework\TestCase;

final class GenericEntityMapperTest extends TestCase
{
    public function testMapReturnsImportedRowWithOriginalData(): void
    {
        $mapper = new GenericEntityMapper();

        $entity = $mapper->map(new Row(1, ['name' => 'Alice']));

        self::assertInstanceOf(ImportedRow::class, $entity);
        self::assertSame(['name' => 'Alice'], $entity->getData());
    }
}
