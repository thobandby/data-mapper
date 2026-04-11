<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

final readonly class LocaleSubscriber implements EventSubscriberInterface
{
    private const LOCALE_QUERY_PARAM = 'locale';

    /**
     * @param list<string> $supportedLocales
     */
    public function __construct(
        private RequestStack $requestStack,
        private string $defaultLocale = 'de',
        private array $supportedLocales = ['de', 'en'],
    ) {
    }

    /**
     * @return array<string, array{0: string, 1: int}>
     */
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 20],
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (! $event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $locale = $this->resolveLocale($request);

        $request->setLocale($locale);

        if (! $request->hasPreviousSession()) {
            return;
        }

        $session = $this->requestStack->getSession();
        if ($session !== null) {
            $session->set('_locale', $locale);
        }
    }

    private function resolveLocale(Request $request): string
    {
        $requestedLocale = (string) $request->query->get(self::LOCALE_QUERY_PARAM, '');
        if ($this->isSupportedLocale($requestedLocale)) {
            return $requestedLocale;
        }

        if ($request->hasPreviousSession()) {
            $session = $this->requestStack->getSession();
            $sessionLocale = $session->get('_locale');
            if (is_string($sessionLocale) && $this->isSupportedLocale($sessionLocale)) {
                return $sessionLocale;
            }
        }

        return $this->defaultLocale;
    }

    private function isSupportedLocale(string $locale): bool
    {
        return in_array($locale, $this->supportedLocales, true);
    }
}
