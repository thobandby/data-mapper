<?php

declare(strict_types=1);

namespace App\Controller;

use App\Service\ImportResultArtifactService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

final class ImportDownloadController extends AbstractController
{
    private const SESSION_RESULT_FILE = 'import.result_file';
    private const SESSION_RESULT_FORMAT = 'import.result_format';

    public function __construct(
        private readonly ImportResultArtifactService $importResultArtifactService,
        private readonly TranslatorInterface $translator,
    ) {
    }

    #[Route('/import/download', name: 'import_download', methods: ['GET'])]
    public function download(Request $request): Response
    {
        $session = $request->getSession();
        $response = $this->importResultArtifactService->downloadResponse(
            (string) $session->get(self::SESSION_RESULT_FILE, ''),
            (string) $session->get(self::SESSION_RESULT_FORMAT, ''),
        );
        if ($response !== null) {
            return $response;
        }

        $this->addFlash('error', $this->translator->trans('flash.download_not_found'));

        return $this->redirectToRoute('import_index');
    }
}
