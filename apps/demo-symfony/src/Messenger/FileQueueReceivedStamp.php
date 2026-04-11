<?php

declare(strict_types=1);

namespace App\Messenger;

use Symfony\Component\Messenger\Stamp\StampInterface;

final readonly class FileQueueReceivedStamp implements StampInterface
{
    public function __construct(
        public string $path,
    ) {
    }
}
