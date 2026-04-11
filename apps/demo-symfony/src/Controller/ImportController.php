<?php

declare(strict_types=1);

namespace App\Controller;

use App\Handler\FileUploadHandler;
use App\Service\ImportMappingStepAction;
use App\Service\ImportProcessStepAction;
use App\Service\ImportSchemaStepAction;
use App\Service\ImportSetupDbAction;
use App\Service\ImportStepResponder;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ImportController extends AbstractController
{
    public function __construct(
        private readonly FileUploadHandler $fileUploadHandler,
        private readonly ImportSchemaStepAction $schemaStepAction,
        private readonly ImportSetupDbAction $setupDbAction,
        private readonly ImportMappingStepAction $mappingStepAction,
        private readonly ImportProcessStepAction $processStepAction,
        private readonly ImportStepResponder $stepResponder,
    ) {
    }

    #[Route('/', name: 'import_index', methods: ['GET', 'POST'])]
    public function index(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            $response = $this->fileUploadHandler->handle($request);
            if ($response !== null) {
                return $response;
            }
        }

        return $this->stepResponder->renderStep($request, 'upload', []);
    }

    #[Route('/import/schema', name: 'import_schema', methods: ['GET', 'POST'])]
    public function schema(Request $request): Response
    {
        return $this->schemaStepAction->handle($request, $this->isCsrfTokenValid(...));
    }

    #[Route('/import/setup-db', name: 'import_setup_db', methods: ['POST'])]
    public function setupDb(Request $request): Response
    {
        return $this->setupDbAction->handle($request, $this->isCsrfTokenValid(...));
    }

    #[Route('/import/mapping', name: 'import_mapping', methods: ['GET', 'POST'])]
    public function mapping(Request $request): Response
    {
        return $this->mappingStepAction->handle($request, $this->isCsrfTokenValid(...));
    }

    #[Route('/import/process', name: 'import_process', methods: ['POST'])]
    public function process(Request $request): Response
    {
        return $this->processStepAction->handle($request, $this->isCsrfTokenValid(...), $this->generateUrl(...));
    }
}
