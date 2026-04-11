<?php

declare(strict_types=1);

namespace App\Messenger;

use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Stamp\TransportMessageIdStamp;
use Symfony\Component\Messenger\Transport\Serialization\SerializerInterface;
use Symfony\Component\Messenger\Transport\TransportInterface;

final class FileQueueTransport implements TransportInterface
{
    public function __construct(
        private readonly SerializerInterface $serializer,
        private readonly string $directory,
    ) {
    }

    /**
     * @return list<Envelope>
     */
    public function get(): iterable
    {
        if (! is_dir($this->directory)) {
            return [];
        }

        $paths = glob($this->directory . '/*.json');
        if ($paths === false) {
            return [];
        }

        sort($paths);

        $envelopes = [];
        foreach ($paths as $path) {
            $envelope = $this->decodeEnvelope($path);
            if ($envelope instanceof Envelope) {
                $envelopes[] = $envelope;
            }
        }

        return $envelopes;
    }

    public function ack(Envelope $envelope): void
    {
        $stamp = $envelope->last(FileQueueReceivedStamp::class);
        if ($stamp instanceof FileQueueReceivedStamp && is_file($stamp->path)) {
            unlink($stamp->path);
        }
    }

    public function reject(Envelope $envelope): void
    {
        $this->ack($envelope);
    }

    public function send(Envelope $envelope): Envelope
    {
        if (! is_dir($this->directory)) {
            mkdir($this->directory, 0o777, true);
        }

        $id = bin2hex(random_bytes(16));
        $encoded = $this->serializer->encode($envelope);

        file_put_contents($this->directory . '/' . $id . '.json', (string) json_encode([
            'id' => $id,
            'body' => base64_encode($encoded['body']),
            'headers' => $encoded['headers'] ?? [],
        ], JSON_THROW_ON_ERROR));

        return $envelope->with(new TransportMessageIdStamp($id));
    }

    private function decodeEnvelope(string $path): ?Envelope
    {
        try {
            $payload = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            unlink($path);

            return null;
        }

        $body = base64_decode((string) ($payload['body'] ?? ''), true);
        if (! is_array($payload) || ! $body) {
            unlink($path);

            return null;
        }

        return $this->serializer->decode([
            'body' => $body,
            'headers' => is_array($payload['headers'] ?? null) ? $payload['headers'] : [],
        ])->with(
            new FileQueueReceivedStamp($path),
            new TransportMessageIdStamp((string) ($payload['id'] ?? basename($path, '.json')))
        );
    }
}
