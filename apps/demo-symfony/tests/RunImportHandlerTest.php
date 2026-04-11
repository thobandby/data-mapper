<?php

declare(strict_types=1);

namespace App\Tests;

use App\Message\ImportSettings;
use App\Message\ImportStatusUpdateMessage;
use App\Message\RunImportMessage;
use App\Messenger\RunImportHandler;
use App\Service\ImportExecutionService;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;

final class RunImportHandlerTest extends TestCase
{
    public function testInvokeDispatchesProcessingAndCompletedUpdates(): void
    {
        $dispatched = [];
        $messageBus = $this->createMock(MessageBusInterface::class);
        $messageBus->method('dispatch')
            ->willReturnCallback(static function (object $message) use (&$dispatched): Envelope {
                $dispatched[] = $message;

                return new Envelope($message);
            });

        $importExecutionService = $this->createMock(ImportExecutionService::class);
        $importExecutionService->method('execute')->willReturn([
            'result' => new \DynamicDataImporter\Domain\Model\ImportResult(5, 4, []),
            'has_artifact' => false,
            'artifact_file' => null,
        ]);
        $handler = new RunImportHandler($importExecutionService, $messageBus, $this->createMock(LoggerInterface::class));
        $handler(new RunImportMessage('job-1', new ImportSettings('stored.csv', 'csv', 'symfony', 'rows', ['name' => 'full_name'], ',')));

        self::assertCount(2, $dispatched);
        self::assertInstanceOf(ImportStatusUpdateMessage::class, $dispatched[0]);
        self::assertSame('processing', $dispatched[0]->status);
        self::assertSame('completed', $dispatched[1]->status);
        self::assertSame(5, $dispatched[1]->payload['result']['processed']);
        self::assertFalse($dispatched[1]->payload['has_artifact']);
        self::assertNull($dispatched[1]->payload['artifact_file']);
    }

    public function testInvokeDispatchesFailedUpdateOnException(): void
    {
        $dispatched = [];
        $messageBus = $this->createMock(MessageBusInterface::class);
        $messageBus->method('dispatch')
            ->willReturnCallback(static function (object $message) use (&$dispatched): Envelope {
                $dispatched[] = $message;

                return new Envelope($message);
            });

        $importExecutionService = $this->createMock(ImportExecutionService::class);
        $importExecutionService->method('execute')->willThrowException(new \RuntimeException('boom'));
        $handler = new RunImportHandler($importExecutionService, $messageBus, $this->createMock(LoggerInterface::class));
        $handler(new RunImportMessage('job-1', new ImportSettings('stored.csv', 'csv', 'symfony', 'rows')));

        self::assertCount(2, $dispatched);
        self::assertSame('processing', $dispatched[0]->status);
        self::assertSame('failed', $dispatched[1]->status);
        self::assertSame('boom', $dispatched[1]->payload['error']);
    }
}
