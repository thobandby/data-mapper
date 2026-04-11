<?php

declare(strict_types=1);

namespace App\Tests;

use App\Service\SchemaSelectionService;
use DynamicDataImporter\Symfony\Messenger\SetupDatabaseMessage;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;

final class SchemaSelectionServiceTest extends TestCase
{
    public function testResolveSelectionUsesExistingTableWithoutDispatching(): void
    {
        $messageBus = $this->createMock(MessageBusInterface::class);
        $messageBus->expects(self::never())->method('dispatch');

        $service = new SchemaSelectionService($messageBus);

        $selection = $service->resolveSelection(
            'existing_rows',
            'ignored_new_table',
            ['first_name', 'email', 'status'],
            ['first_name', 'email', 'status'],
            ['0', '2', 'missing'],
            'symfony',
        );

        self::assertSame([
            'table' => 'existing_rows',
            'target_columns' => ['first_name', 'status'],
            'mapping' => [
                'first_name' => 'first_name',
                'email' => '',
                'status' => 'status',
            ],
            'db_setup_dispatched' => false,
            'db_setup_error' => null,
        ], $selection);
    }

    public function testResolveSelectionDispatchesSetupForNewSymfonyTable(): void
    {
        $messageBus = $this->createMock(MessageBusInterface::class);
        $messageBus->expects(self::once())
            ->method('dispatch')
            ->with(self::callback(static function (object $message): bool {
                return $message instanceof SetupDatabaseMessage
                    && $message->tableName === 'imported_rows'
                    && $message->columns === ['name', 'email'];
            }))
            ->willReturnCallback(static fn (object $message): Envelope => new Envelope($message));

        $service = new SchemaSelectionService($messageBus);

        $selection = $service->resolveSelection(
            '',
            'imported_rows',
            ['name', 'email', 'status'],
            ['first_name', 'email', 'status'],
            [0, 1],
            'symfony',
        );

        self::assertSame([
            'table' => 'imported_rows',
            'target_columns' => ['name', 'email'],
            'mapping' => [
                'first_name' => 'name',
                'email' => 'email',
                'status' => '',
            ],
            'db_setup_dispatched' => true,
            'db_setup_error' => null,
        ], $selection);
    }

    public function testResolveSelectionSkipsDispatchForNonSymfonyAdapter(): void
    {
        $messageBus = $this->createMock(MessageBusInterface::class);
        $messageBus->expects(self::never())->method('dispatch');

        $service = new SchemaSelectionService($messageBus);

        $selection = $service->resolveSelection(
            '',
            'imported_rows',
            ['name', 'email'],
            ['name', 'email'],
            [1],
            'json',
        );

        self::assertSame([
            'table' => 'imported_rows',
            'target_columns' => ['email'],
            'mapping' => [
                'name' => '',
                'email' => 'email',
            ],
            'db_setup_dispatched' => false,
            'db_setup_error' => null,
        ], $selection);
    }

    public function testResolveSelectionSurfacesDispatchFailure(): void
    {
        $messageBus = $this->createMock(MessageBusInterface::class);
        $messageBus->expects(self::once())
            ->method('dispatch')
            ->willThrowException(new \RuntimeException('transport unavailable'));

        $service = new SchemaSelectionService($messageBus);

        $selection = $service->resolveSelection(
            '',
            'imported_rows',
            ['name', 'email'],
            ['name', 'email'],
            [0, 1],
            'symfony',
        );

        self::assertSame([
            'table' => 'imported_rows',
            'target_columns' => ['name', 'email'],
            'mapping' => [
                'name' => 'name',
                'email' => 'email',
            ],
            'db_setup_dispatched' => false,
            'db_setup_error' => 'transport unavailable',
        ], $selection);
    }
}
