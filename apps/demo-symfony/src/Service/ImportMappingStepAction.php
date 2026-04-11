<?php

declare(strict_types=1);

namespace App\Service;

use App\Import\Http\ImportContextResolver;
use DynamicDataImporter\Domain\Exception\ImporterException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final readonly class ImportMappingStepAction
{
    private const CSRF_MAPPING = 'import_mapping';

    public function __construct(
        private ImportContextResolver $importContextResolver,
        private ImportPreviewService $importPreviewService,
        private ImportExceptionMessageFormatter $importExceptionMessageFormatter,
        private ImportFlashMessenger $flashMessenger,
        private ImportStepResponder $responder,
    ) {
    }

    public function handle(Request $request, callable $isCsrfTokenValid): Response
    {
        $context = $this->importContextResolver->resolve($request);
        $fileInfo = $context->fileInfo();
        $redirectParameters = [
            'table' => $context->tableName,
            'mapping' => $context->mapping,
            'delimiter' => $context->delimiter,
        ];

        try {
            $preview = $this->importPreviewService->buildMappingPreview(
                $context->file,
                $context->fileType,
                $context->adapter,
                $context->tableName,
                $context->mapping,
                $context->targetColumns,
                $context->delimiter,
            );
        } catch (ImporterException $e) {
            $this->flashMessenger->add('error', $this->importExceptionMessageFormatter->formatForUser($e));

            return $this->responder->redirectToRoute('import_index');
        }

        if ($request->isMethod('POST') && ! $isCsrfTokenValid(self::CSRF_MAPPING, (string) $request->request->get('_token'))) {
            $this->flashMessenger->error('flash.invalid_csrf');

            return $this->responder->redirectToRoute('import_mapping', array_merge($fileInfo, $redirectParameters));
        }

        return $this->responder->renderStep($request, 'mapping', array_merge($fileInfo, [
            'table_name' => $context->tableName,
            ...$preview,
        ]));
    }
}
