<?php

declare(strict_types=1);

namespace App\Service;

use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

final readonly class ImportFlashMessenger
{
    public function __construct(
        private RequestStack $requestStack,
        private TranslatorInterface $translator,
    ) {
    }

    /**
     * @param array<string, scalar|null> $parameters
     */
    public function add(string $type, string $messageKey, array $parameters = []): void
    {
        $session = $this->requestStack->getSession();
        if (! method_exists($session, 'getFlashBag')) {
            return;
        }

        /** @var FlashBagInterface $flashBag */
        $flashBag = $session->getFlashBag();
        $flashBag->add($type, $this->translator->trans($messageKey, $parameters));
    }

    /**
     * @param array<string, scalar|null> $parameters
     */
    public function error(string $messageKey, array $parameters = []): void
    {
        $this->add('error', $messageKey, $parameters);
    }

    /**
     * @param array<string, scalar|null> $parameters
     */
    public function success(string $messageKey, array $parameters = []): void
    {
        $this->add('success', $messageKey, $parameters);
    }

    /**
     * @param array<string, scalar|null> $parameters
     */
    public function info(string $messageKey, array $parameters = []): void
    {
        $this->add('info', $messageKey, $parameters);
    }
}
