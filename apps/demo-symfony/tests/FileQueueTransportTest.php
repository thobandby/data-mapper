<?php

declare(strict_types=1);

namespace App\Tests;

use App\Message\ImportSettings;
use App\Message\RunImportMessage;
use App\Messenger\FileQueueTransport;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Transport\Serialization\PhpSerializer;

final class FileQueueTransportTest extends TestCase
{
    private string $directory;

    protected function setUp(): void
    {
        $this->directory = sys_get_temp_dir() . '/file-queue-transport-test-' . bin2hex(random_bytes(6));
    }

    protected function tearDown(): void
    {
        if (is_dir($this->directory)) {
            array_map('unlink', glob($this->directory . '/*') ?: []);
            rmdir($this->directory);
        }
    }

    public function testSendGetAndAckEnvelope(): void
    {
        $transport = new FileQueueTransport(new PhpSerializer(), $this->directory);
        $envelope = new Envelope(new RunImportMessage('job-1', new ImportSettings('stored.csv', 'csv', 'symfony', 'rows')));

        $transport->send($envelope);
        $received = iterator_to_array($transport->get());

        self::assertCount(1, $received);
        self::assertInstanceOf(RunImportMessage::class, $received[0]->getMessage());
        self::assertFileExists(glob($this->directory . '/*.json')[0]);

        $transport->ack($received[0]);

        self::assertSame([], glob($this->directory . '/*.json') ?: []);
    }
}
