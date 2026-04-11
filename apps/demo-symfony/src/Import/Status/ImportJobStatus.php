<?php

declare(strict_types=1);

namespace App\Import\Status;

enum ImportJobStatus: string
{
    case Queued = 'queued';
    case Processing = 'processing';
    case Completed = 'completed';
    case Failed = 'failed';
}
