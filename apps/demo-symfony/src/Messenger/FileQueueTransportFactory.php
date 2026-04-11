<?php

declare(strict_types=1);

namespace App\Messenger;

use Symfony\Component\Messenger\Exception\InvalidArgumentException;
use Symfony\Component\Messenger\Transport\Serialization\SerializerInterface;
use Symfony\Component\Messenger\Transport\TransportFactoryInterface;
use Symfony\Component\Messenger\Transport\TransportInterface;

/**
 * @implements TransportFactoryInterface<FileQueueTransport>
 */
final class FileQueueTransportFactory implements TransportFactoryInterface
{
    /**
     * @param array<string, mixed> $options
     */
    public function createTransport(string $dsn, array $options, SerializerInterface $serializer): TransportInterface
    {
        if (! $this->supports($dsn, $options)) {
            throw new InvalidArgumentException(sprintf('Unsupported DSN "%s".', $dsn));
        }

        $queueName = trim((string) parse_url($dsn, PHP_URL_HOST));
        if ($queueName === '') {
            $queueName = ltrim((string) parse_url($dsn, PHP_URL_PATH), '/');
        }
        if ($queueName === '') {
            $queueName = 'default';
        }

        return new FileQueueTransport(
            $serializer,
            sys_get_temp_dir() . '/dynamic-data-importer/messenger/' . $queueName,
        );
    }

    /**
     * @param array<string, mixed> $options
     */
    public function supports(string $dsn, array $options): bool
    {
        unset($options);

        return str_starts_with($dsn, 'filequeue://');
    }
}
