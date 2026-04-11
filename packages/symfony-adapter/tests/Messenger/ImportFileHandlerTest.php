<?php

declare(strict_types=1);

namespace DynamicDataImporter\Tests\Symfony\Messenger;

use DynamicDataImporter\Application\UseCase\ImportFile;
use DynamicDataImporter\Domain\Model\Row;
use DynamicDataImporter\Port\Persistence\TableAwarePersisterInterface;
use DynamicDataImporter\Port\Reader\TabularReaderInterface;
use DynamicDataImporter\Symfony\Messenger\ImportFileHandler;
use DynamicDataImporter\Symfony\Messenger\ImportFileMessage;
use PHPUnit\Framework\TestCase;

class ImportFileHandlerTest extends TestCase
{
    public function testInvoke(): void
    {
        $reader = $this->createMock(TabularReaderInterface::class);
        $reader->expects($this->once())
            ->method('rows')
            ->willReturn([new Row(1, ['name' => 'Alice'])]);

        $message = new ImportFileMessage($reader, 'custom_table');

        $persister = new class implements TableAwarePersisterInterface {
            public string $lastTableName = '';
            public int $persistedCount = 0;
            public int $flushCount = 0;

            public function useTableName(string $name): void
            {
                $this->lastTableName = $name;
            }

            public function persist(object $entity): void
            {
                ++$this->persistedCount;
            }

            public function flush(): void
            {
                ++$this->flushCount;
            }
        };

        $importFile = new ImportFile($persister);
        $handler = new ImportFileHandler($importFile, $persister);
        ($handler)($message);

        $this->assertEquals('custom_table', $persister->lastTableName);
        $this->assertSame(1, $persister->persistedCount);
        $this->assertSame(1, $persister->flushCount);
    }
}
