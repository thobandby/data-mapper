<?php

declare(strict_types=1);

namespace App\Service;

use App\Import\Http\UploadRequestError;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

final readonly class WebUploadFlow
{
    private const CSRF_IMPORT = 'import';
    private const DEFAULT_ADAPTER = 'memory';

    public function __construct(
        private ImportRateLimiter $importRateLimiter,
        private ImportUploadRequestService $importUploadRequestService,
        private CsrfTokenManagerInterface $csrfTokenManager,
        private ImportFlashMessenger $flashMessenger,
        private ImportStepResponder $responder,
    ) {
    }

    public function handle(Request $request): RedirectResponse
    {
        $rateLimit = $this->importRateLimiter->consumeWebUpload($request->getClientIp());
        if (! $rateLimit['allowed']) {
            $this->flashMessenger->error('upload.error.rate_limited', ['%seconds%' => (string) $rateLimit['retry_after']]);

            return $this->responder->redirectToRoute('import_index');
        }

        if (! $this->csrfTokenManager->isTokenValid(new CsrfToken(self::CSRF_IMPORT, (string) $request->request->get('_token')))) {
            $this->flashMessenger->error('flash.invalid_csrf');

            return $this->responder->redirectToRoute('import_index');
        }

        return $this->handleUploadRequest($request);
    }

    private function handleUploadRequest(Request $request): RedirectResponse
    {
        $file = $request->files->get('file');
        $response = $this->uploadedFileErrorResponse($file);
        if ($response !== null) {
            return $response;
        }

        \assert($file instanceof UploadedFile);
        $resolvedRequest = $this->importUploadRequestService->resolveForWeb(
            $file,
            (string) $request->request->get('adapter', self::DEFAULT_ADAPTER),
            (string) $request->request->get('file_type', 'auto'),
            (string) $request->request->get('delimiter', ''),
        );

        return $this->resolvedUploadResponse($resolvedRequest);
    }

    private function uploadedFileErrorResponse(mixed $file): ?RedirectResponse
    {
        if (! $file instanceof UploadedFile) {
            $this->flashMessenger->error('upload.error.no_file_uploaded');

            return $this->responder->redirectToRoute('import_index');
        }

        if (! $file->isValid()) {
            $this->flashMessenger->error('upload.error.upload_failed', ['%reason%' => $file->getErrorMessage()]);

            return $this->responder->redirectToRoute('import_index');
        }

        return null;
    }

    private function resolvedUploadResponse(UploadRequestError|\App\Import\Http\ResolvedUploadRequest $resolvedRequest): RedirectResponse
    {
        if ($resolvedRequest instanceof UploadRequestError) {
            $this->flashMessenger->error($resolvedRequest->messageKey, $resolvedRequest->parameters);

            return $this->responder->redirectToRoute('import_index');
        }

        $parameters = $resolvedRequest->fileInfo();
        if ($resolvedRequest->delimiter !== null) {
            $parameters['delimiter'] = $resolvedRequest->delimiter;
        }

        return $this->responder->redirectToRoute('import_schema', $parameters);
    }
}
