<?php

declare(strict_types=1);

namespace App\Service;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Environment;

final readonly class ImportStepResponder
{
    public function __construct(
        private Environment $twig,
        private UrlGeneratorInterface $urlGenerator,
        private ImportManager $importManager,
    ) {
    }

    /**
     * @param array<string, mixed> $context
     */
    public function renderStep(Request $request, string $step, array $context): Response
    {
        $template = sprintf('import/steps/%s.html.twig', $step);
        $stepContext = array_merge($context, [
            'current_step' => $step,
            'max_upload_size_mb' => $this->importManager->maxUploadMegabytes(),
        ]);

        if ($this->isFrameRequest($request)) {
            return new Response($this->twig->render('import/frame.html.twig', [
                'step_template' => $template,
                'step_context' => $stepContext,
            ]));
        }

        return new Response($this->twig->render('import/wizard.html.twig', [
            'current_step' => $step,
            'step_template' => $template,
            'step_context' => $stepContext,
        ]));
    }

    /**
     * @param array<string, mixed> $parameters
     */
    public function redirectToRoute(string $route, array $parameters = []): RedirectResponse
    {
        return new RedirectResponse($this->urlGenerator->generate($route, $parameters));
    }

    private function isFrameRequest(Request $request): bool
    {
        return $request->headers->get('Turbo-Frame') === 'import_step'
            || $request->query->getBoolean('_frame');
    }
}
