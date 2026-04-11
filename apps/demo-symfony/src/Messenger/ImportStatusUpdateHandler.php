<?php

declare(strict_types=1);

namespace App\Messenger;

use App\Import\Status\ImportJobStore;
use App\Message\ImportStatusUpdateMessage;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class ImportStatusUpdateHandler
{
    public function __construct(
        private ImportJobStore $importJobStore,
    ) {
    }

    public function __invoke(ImportStatusUpdateMessage $message): void
    {
        $this->importJobStore->update($message->jobId, $message->status, $message->payload);
    }
}
