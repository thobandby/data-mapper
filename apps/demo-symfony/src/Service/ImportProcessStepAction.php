<?php

declare(strict_types=1);

namespace App\Service;

use App\Import\Http\ImportContext;
use App\Import\Http\ImportContextResolver;
use DynamicDataImporter\Domain\Exception\ImporterException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final readonly class ImportProcessStepAction
{
    public const SESSION_RESULT_FILE = 'import.result_file';
    public const SESSION_RESULT_FORMAT = 'import.result_format';

    private const CSRF_PROCESS = 'import_process';

    public function __construct(
        private ImportContextResolver $importContextResolver,
        private AsyncImportService $asyncImportService,
        private ImportExecutionService $importExecutionService,
        private ImportResultArtifactService $importResultArtifactService,
        private ImportExceptionMessageFormatter $importExceptionMessageFormatter,
        private ImportFlashMessenger $flashMessenger,
        private ImportStepResponder $responder,
    ) {
    }

    public function handle(Request $request, callable $isCsrfTokenValid, callable $generateUrl): Response
    {
        $context = $this->importContextResolver->resolve($request);
        $fileInfo = $context->fileInfo();
        if (! $isCsrfTokenValid(self::CSRF_PROCESS, (string) $request->request->get('_token'))) {
            $this->flashMessenger->error('flash.invalid_csrf');

            return $this->responder->redirectToRoute('import_mapping', array_merge($fileInfo, [
                'table' => $context->tableName,
                'mapping' => $context->mapping,
            ]));
        }

        return $context->adapter === 'symfony'
            ? $this->handleAsyncImport($request, $context, $generateUrl)
            : $this->handleSynchronousImport($request, $context);
    }

    private function handleAsyncImport(Request $request, ImportContext $context, callable $generateUrl): Response
    {
        $job = $this->asyncImportService->dispatch(
            $context->file,
            $context->fileType,
            $context->adapter,
            $context->tableName,
            $context->mapping,
            $context->delimiter,
        );

        $this->flashMessenger->success('flash.import_queued');

        return $this->responder->renderStep($request, 'result', [
            'adapter' => $context->adapter,
            'async_job' => $job,
            'async_status_url' => $generateUrl('api_import_status', ['jobId' => $job['id']]),
        ]);
    }

    private function handleSynchronousImport(Request $request, ImportContext $context): Response
    {
        try {
            $execution = $this->importExecutionService->execute(
                $context->file,
                $context->fileType,
                $context->adapter,
                $context->tableName,
                $context->mapping,
                $context->delimiter,
            );
        } catch (ImporterException $e) {
            $this->flashMessenger->add('error', $this->importExceptionMessageFormatter->formatForUser($e));

            return $this->responder->redirectToRoute('import_index');
        }

        $this->storeExecutionArtifacts($request, $context->adapter, $execution);

        return $this->responder->renderStep($request, 'result', [
            'result' => $execution['result'],
            'adapter' => $context->adapter,
        ]);
    }

    /**
     * @param array{result: mixed, has_artifact: bool, artifact_file: ?string} $execution
     */
    private function storeExecutionArtifacts(Request $request, string $adapter, array $execution): void
    {
        $session = $request->getSession();
        $currentArtifactPath = (string) $session->get(self::SESSION_RESULT_FILE, '');

        if ($execution['has_artifact']) {
            $this->importResultArtifactService->replace($currentArtifactPath, $execution['artifact_file']);
            if ($execution['artifact_file'] !== null) {
                $session->set(self::SESSION_RESULT_FILE, $execution['artifact_file']);
                $session->set(self::SESSION_RESULT_FORMAT, $adapter);
                $this->flashMessenger->info('flash.artifact_saved', [
                    '%path%' => $execution['artifact_file'],
                    '%format%' => strtoupper($adapter),
                ]);
            }

            return;
        }

        $this->importResultArtifactService->replace($currentArtifactPath, null);
        $session->remove(self::SESSION_RESULT_FILE);
        $session->remove(self::SESSION_RESULT_FORMAT);
    }
}
