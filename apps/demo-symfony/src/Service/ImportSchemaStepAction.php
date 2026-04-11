<?php

declare(strict_types=1);

namespace App\Service;

use App\Import\Http\ImportContextResolver;
use DynamicDataImporter\Domain\Exception\ImporterException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final readonly class ImportSchemaStepAction
{
    private const CSRF_SCHEMA = 'import_schema';

    public function __construct(
        private ImportContextResolver $importContextResolver,
        private SchemaSelectionService $schemaSelectionService,
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

        if ($request->isMethod('POST')) {
            return $this->handlePost($request, $isCsrfTokenValid, $fileInfo, $context->adapter, $context->delimiter);
        }

        return $this->responder->renderStep($request, 'schema', array_merge($fileInfo, [
            'table_name' => $context->tableName,
            ...$this->loadPreview($context->file, $context->fileType, $context->adapter, $context->delimiter),
        ]));
    }

    /**
     * @param array<string, mixed> $fileInfo
     */
    private function handlePost(
        Request $request,
        callable $isCsrfTokenValid,
        array $fileInfo,
        string $adapter,
        ?string $delimiter,
    ): Response {
        if (! $isCsrfTokenValid(self::CSRF_SCHEMA, (string) $request->request->get('_token'))) {
            $this->flashMessenger->error('flash.invalid_csrf');

            return $this->responder->redirectToRoute('import_schema', $fileInfo);
        }

        $selection = $this->schemaSelectionService->resolveSelection(
            (string) $request->request->get('table'),
            $this->importContextResolver->resolve($request, 'new_table_name')->tableName,
            $request->request->all('columns'),
            $request->request->all('source_headers'),
            array_values($request->request->all('selected_columns')),
            $adapter,
        );
        $this->flashSelectionMessages($selection);

        return $this->responder->redirectToRoute('import_mapping', array_merge($fileInfo, [
            'table' => $selection['table'],
            'mapping' => $selection['mapping'],
            'target_columns' => $selection['target_columns'],
            'delimiter' => $delimiter,
        ]));
    }

    /**
     * @return array{
     *     headers: array<int, string>,
     *     sample: mixed,
     *     delimiter: ?string,
     *     existing_tables: list<string>
     * }
     */
    private function loadPreview(string $file, string $fileType, string $adapter, ?string $delimiter): array
    {
        try {
            return $this->importPreviewService->buildSchemaPreview($file, $fileType, $adapter, $delimiter);
        } catch (ImporterException $e) {
            $this->flashMessenger->add('error', $this->importExceptionMessageFormatter->formatForUser($e));

            return [
                'headers' => [],
                'sample' => [],
                'delimiter' => null,
                'existing_tables' => [],
            ];
        }
    }

    /**
     * @param array{db_setup_dispatched: bool, db_setup_error: ?string} $selection
     */
    private function flashSelectionMessages(array $selection): void
    {
        if ($selection['db_setup_dispatched']) {
            $this->flashMessenger->success('flash.db_table_creation_dispatched');
        }

        if ($selection['db_setup_error'] !== null) {
            $this->flashMessenger->error('flash.setup_dispatch_failed', ['%reason%' => $selection['db_setup_error']]);
        }
    }
}
