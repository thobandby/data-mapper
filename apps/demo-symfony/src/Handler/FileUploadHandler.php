<?php

declare(strict_types=1);

namespace App\Handler;

use App\Service\WebUploadFlow;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final readonly class FileUploadHandler
{
    public function __construct(
        private WebUploadFlow $webUploadFlow,
    ) {
    }

    public function handle(Request $request): ?Response
    {
        return $this->webUploadFlow->handle($request);
    }
}
