<?php

declare(strict_types=1);

namespace App\Service;

use App\Import\Http\ImportContextResolver;
use DynamicDataImporter\Symfony\Messenger\SetupDatabaseMessage;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;

final readonly class ImportSetupDbAction
{
    private const string CSRF_SETUP_DB = 'import_setup_db';

    public function __construct(
        private ImportContextResolver $importContextResolver,
        private MessageBusInterface $messageBus,
        private ImportFlashMessenger $flashMessenger,
        private ImportStepResponder $responder,
    ) {
    }

    public function handle(Request $request, callable $isCsrfTokenValid): Response
    {
        $context = $this->importContextResolver->resolve($request, 'table_name');
        $fileInfo = $context->fileInfo();
        $columns = $request->request->all('columns');
        $redirectParameters = array_merge($fileInfo, [
            'table' => $context->tableName,
            'mapping' => $context->mapping,
            'target_columns' => $columns,
            'delimiter' => $context->delimiter,
        ]);

        if (! $isCsrfTokenValid(self::CSRF_SETUP_DB, (string) $request->request->get('_token'))) {
            $this->flashMessenger->error('flash.invalid_csrf');

            return $this->responder->redirectToRoute('import_mapping', $redirectParameters);
        }

        try {
            $this->messageBus->dispatch(new SetupDatabaseMessage($context->tableName, $columns));
            $this->flashMessenger->success('flash.db_setup_dispatched', ['%table%' => $context->tableName]);
        } catch (\Throwable $e) {
            $this->flashMessenger->error('flash.setup_dispatch_failed', ['%reason%' => $e->getMessage()]);
        }

        return $this->responder->redirectToRoute('import_mapping', $redirectParameters);
    }
}
