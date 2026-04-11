<?php

declare(strict_types=1);

namespace DynamicDataImporter\Infrastructure\Api;

use DynamicDataImporter\Application\Service\ImportWorkflowService;

final readonly class ApiImportActionHandler
{
    public function __construct(
        private ImportWorkflowService $workflow,
        private ApiRequest $request,
        private ApiResponder $responder,
    ) {
    }

    public function analyze(): void
    {
        $this->respondSafely(function (): void {
            $upload = $this->request->requireUpload();
            $this->responder->jsonResponse($this->workflow->analyze(
                $upload['tmp_name'],
                $this->request->requestString('fileType', $upload['file_type']),
                $this->request->requestNullableString('delimiter'),
                $this->request->requestInt('sampleSize', 5),
                $this->request->requestMapping(),
            ));
        });
    }

    public function preview(): void
    {
        $this->respondSafely(function (): void {
            $upload = $this->request->requireUpload();
            $this->responder->jsonResponse($this->workflow->preview(
                $upload['tmp_name'],
                $this->request->requestString('fileType', $upload['file_type']),
                $this->request->requestNullableString('delimiter'),
                $this->request->requestInt('sampleSize', 5),
                $this->request->requestMapping(),
            ));
        });
    }

    public function execute(bool $legacySqlResponse): void
    {
        $this->respondSafely(function () use ($legacySqlResponse): void {
            $upload = $this->request->requireUpload();
            $execution = $this->workflow->execute(
                $upload['tmp_name'],
                $this->request->requestString('fileType', $upload['file_type']),
                $this->request->requestNullableString('delimiter'),
                $this->request->requestMapping(),
                $this->request->requestString('outputFormat', $legacySqlResponse ? 'sql' : 'memory'),
                $this->request->requestString('tableName', 'imported_data'),
            );

            if ($legacySqlResponse) {
                header('Content-Type: text/plain');
                echo $execution['output']['sql'] ?? '';

                return;
            }

            $this->responder->jsonResponse($execution);
        });
    }

    private function respondSafely(\Closure $callback): void
    {
        try {
            $callback();
        } catch (\Throwable $exception) {
            $this->responder->errorResponse($exception);
        }
    }
}
