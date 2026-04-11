<?php

declare(strict_types=1);

namespace App\Controller;

use App\Import\Status\ImportJobStore;
use App\Service\AsyncImportService;
use App\Service\ImportRateLimiter;
use App\Service\ImportUploadRequestService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

final class ApiImportController extends AbstractController
{
    public function __construct(
        private readonly ImportRateLimiter $importRateLimiter,
        private readonly ImportUploadRequestService $importUploadRequestService,
        private readonly AsyncImportService $asyncImportService,
        private readonly ImportJobStore $importJobStore,
        private readonly RequestStack $requestStack,
        private readonly TranslatorInterface $translator,
    ) {
    }

    #[Route('/api/imports', name: 'api_import_start', methods: ['POST'])]
    public function start(Request $request): JsonResponse
    {
        $rateLimitResponse = $this->rateLimitResponse(
            $this->importRateLimiter->consumeApiImportStart($request->getClientIp())
        );
        if ($rateLimitResponse !== null) {
            return $rateLimitResponse;
        }

        return $this->createImportStartResponse($request);
    }

    #[Route('/api/imports/{jobId}', name: 'api_import_status', methods: ['GET'])]
    public function status(string $jobId): JsonResponse
    {
        $request = $this->requestStack->getCurrentRequest();
        if ($request instanceof Request) {
            $limitResponse = $this->rateLimitResponse(
                $this->importRateLimiter->consumeApiImportStatus($request->getClientIp())
            );
            if ($limitResponse !== null) {
                return $limitResponse;
            }
        }

        $job = $this->importJobStore->get($jobId);

        return $job === null
            ? $this->json(['error' => $this->translator->trans('api.error.job_not_found')], Response::HTTP_NOT_FOUND)
            : $this->json($job);
    }

    private function createImportStartResponse(Request $request): JsonResponse
    {
        $file = $request->files->get('file');
        if (! $file instanceof UploadedFile) {
            return $this->errorResponse('api.error.no_file_uploaded');
        }

        if (! $file->isValid()) {
            return $this->errorResponse('api.error.upload_failed', ['%reason%' => $file->getErrorMessage()]);
        }

        return $this->createValidUploadResponse($request, $file);
    }

    private function createValidUploadResponse(Request $request, UploadedFile $file): JsonResponse
    {
        $resolvedRequest = $this->importUploadRequestService->resolveForApi(
            $file,
            (string) $request->request->get('adapter', 'symfony'),
            (string) $request->request->get('file_type', 'auto'),
            (string) $request->request->get('table_name', 'imported_rows'),
            (string) $request->request->get('delimiter', ''),
            (string) $request->request->get('mapping', ''),
        );

        if ($resolvedRequest instanceof \App\Import\Http\UploadRequestError) {
            return $this->errorResponse($resolvedRequest->messageKey, $resolvedRequest->parameters);
        }

        return $this->json($this->createQueuedJobPayload($resolvedRequest), Response::HTTP_ACCEPTED);
    }

    /**
     * @return array{job_id: mixed, status: mixed, status_url: string}
     */
    private function createQueuedJobPayload(\App\Import\Http\ResolvedUploadRequest $resolvedRequest): array
    {
        $job = $this->asyncImportService->dispatch(
            $resolvedRequest->storedFile,
            $resolvedRequest->fileType,
            $resolvedRequest->adapter,
            $resolvedRequest->tableName,
            $resolvedRequest->mapping,
            $resolvedRequest->delimiter,
        );

        return [
            'job_id' => $job['id'],
            'status' => $job['status'],
            'status_url' => $this->generateUrl('api_import_status', ['jobId' => $job['id']]),
        ];
    }

    /**
     * @param array<string, scalar|null> $parameters
     */
    private function errorResponse(string $messageKey, array $parameters = []): JsonResponse
    {
        return $this->json(['error' => $this->translator->trans($messageKey, $parameters)], Response::HTTP_BAD_REQUEST);
    }

    /**
     * @param array{allowed: bool, limit: int, remaining: int, retry_after: int} $rateLimit
     */
    private function rateLimitResponse(array $rateLimit): ?JsonResponse
    {
        if ($rateLimit['allowed']) {
            return null;
        }

        $response = $this->json([
            'error' => $this->translator->trans('api.error.rate_limited', ['%seconds%' => $rateLimit['retry_after']]),
        ], Response::HTTP_TOO_MANY_REQUESTS);
        $response->headers->set('Retry-After', (string) $rateLimit['retry_after']);

        return $response;
    }
}
