<?php

declare(strict_types=1);

namespace App\Tests;

use App\Handler\FileUploadHandler;
use App\Service\ImportFlashMessenger;
use App\Service\ImportManager;
use App\Service\ImportRateLimiter;
use App\Service\ImportStepResponder;
use App\Service\ImportUploadRequestService;
use App\Service\ImportUploadRequestValidator;
use App\Service\WebUploadFlow;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Validator\Validation;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;

final class FileUploadHandlerTest extends TestCase
{
    /** @var list<string> */
    private array $cleanupFiles = [];

    protected function tearDown(): void
    {
        foreach ($this->cleanupFiles as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
    }

    public function testHandleRedirectsAndFlashesOnInvalidCsrf(): void
    {
        $csrf = $this->createMock(CsrfTokenManagerInterface::class);
        $csrf->method('isTokenValid')->willReturn(false);

        $handler = $this->createHandler(
            $this->createRateLimiter($this->allowedRateLimit()),
            $this->createUploadRequestService($this->createMock(ImportManager::class)),
            $csrf,
            $requestStack = new RequestStack(),
        );

        $request = Request::create('/', 'POST', ['_token' => 'bad']);
        $session = $this->attachSession($requestStack, $request);

        $response = $handler->handle($request);

        self::assertNotNull($response);
        self::assertSame('/import_index', $response->headers->get('Location'));
        self::assertSame(['Ungültiges CSRF-Token.'], $session->getFlashBag()->get('error'));
    }

    public function testHandleFlashesErrorWhenNoFileIsUploaded(): void
    {
        $csrf = $this->createMock(CsrfTokenManagerInterface::class);
        $csrf->method('isTokenValid')->willReturn(true);

        $handler = $this->createHandler(
            $this->createRateLimiter($this->allowedRateLimit()),
            $this->createUploadRequestService($this->createMock(ImportManager::class)),
            $csrf,
            $requestStack = new RequestStack(),
        );

        $request = Request::create('/', 'POST', ['_token' => 'ok']);
        $session = $this->attachSession($requestStack, $request);

        $response = $handler->handle($request);

        self::assertNotNull($response);
        self::assertSame('/import_index', $response->headers->get('Location'));
        self::assertSame(['Bitte wähle eine Datei für den Import aus.'], $session->getFlashBag()->get('error'));
    }

    public function testHandleFlashesErrorForInvalidUploadedFile(): void
    {
        $csrf = $this->createMock(CsrfTokenManagerInterface::class);
        $csrf->method('isTokenValid')->willReturn(true);

        $handler = $this->createHandler(
            $this->createRateLimiter($this->allowedRateLimit()),
            $this->createUploadRequestService($this->createMock(ImportManager::class)),
            $csrf,
            $requestStack = new RequestStack(),
        );

        $tempFile = tempnam(sys_get_temp_dir(), 'upload_invalid_');
        self::assertNotFalse($tempFile);
        $this->cleanupFiles[] = $tempFile;

        $file = new UploadedFile($tempFile, 'sample.csv', 'text/csv', \UPLOAD_ERR_NO_FILE, true);
        $request = Request::create('/', 'POST', ['_token' => 'ok'], [], ['file' => $file]);
        $session = $this->attachSession($requestStack, $request);

        $response = $handler->handle($request);
        $errors = $session->getFlashBag()->peek('error');

        self::assertNotNull($response);
        self::assertSame('/import_index', $response->headers->get('Location'));
        self::assertCount(1, $errors);
        self::assertStringStartsWith('Datei-Upload fehlgeschlagen: ', $errors[0]);
    }

    public function testHandleAcceptsXmlUploads(): void
    {
        $importManager = $this->createMock(ImportManager::class);
        $importManager->method('getEffectiveFileType')->willReturn('xml');
        $importManager->method('maxUploadBytes')->willReturn(10_485_760);
        $importManager->method('maxUploadMegabytes')->willReturn(10);
        $importManager->method('storeUploadedFile')->willReturn('stored.xml');

        $csrf = $this->createMock(CsrfTokenManagerInterface::class);
        $csrf->method('isTokenValid')->willReturn(true);

        $handler = $this->createHandler(
            $this->createRateLimiter($this->allowedRateLimit()),
            $this->createUploadRequestService($importManager),
            $csrf,
            $requestStack = new RequestStack(),
        );

        $file = $this->createUploadedFile('sample.xml', 'application/xml', '<root><item>Alice</item></root>');
        $request = Request::create('/', 'POST', ['_token' => 'ok', 'file_type' => 'auto'], [], ['file' => $file]);
        $session = $this->attachSession($requestStack, $request);

        $response = $handler->handle($request);

        self::assertNotNull($response);
        self::assertSame('/import_schema?file=stored.xml&adapter=memory&file_type=xml', $response->headers->get('Location'));
        self::assertSame([], $session->getFlashBag()->get('error'));
    }

    public function testHandleRedirectsToSchemaForValidUploads(): void
    {
        $importManager = $this->createMock(ImportManager::class);
        $importManager->method('getEffectiveFileType')->willReturn('csv');
        $importManager->method('maxUploadBytes')->willReturn(10_485_760);
        $importManager->method('maxUploadMegabytes')->willReturn(10);
        $importManager->method('storeUploadedFile')->willReturn('stored.csv');

        $csrf = $this->createMock(CsrfTokenManagerInterface::class);
        $csrf->method('isTokenValid')->willReturn(true);

        $handler = $this->createHandler(
            $this->createRateLimiter($this->allowedRateLimit()),
            $this->createUploadRequestService($importManager),
            $csrf,
            $requestStack = new RequestStack(),
        );

        $file = $this->createUploadedFile('sample.csv');
        $request = Request::create('/', 'POST', [
            '_token' => 'ok',
            'file_type' => 'csv',
            'adapter' => 'json',
        ], [], ['file' => $file]);
        $this->attachSession($requestStack, $request);

        $response = $handler->handle($request);

        self::assertNotNull($response);
        self::assertSame('/import_schema?file=stored.csv&adapter=json&file_type=csv', $response->headers->get('Location'));
    }

    public function testHandleRejectsUnsupportedAdapterValues(): void
    {
        $importManager = $this->createMock(ImportManager::class);
        $importManager->expects(self::never())->method('storeUploadedFile');
        $importManager->method('getEffectiveFileType')->willReturn('csv');
        $importManager->method('maxUploadBytes')->willReturn(10_485_760);
        $importManager->method('maxUploadMegabytes')->willReturn(10);

        $csrf = $this->createMock(CsrfTokenManagerInterface::class);
        $csrf->method('isTokenValid')->willReturn(true);

        $handler = $this->createHandler(
            $this->createRateLimiter($this->allowedRateLimit()),
            $this->createUploadRequestService($importManager),
            $csrf,
            $requestStack = new RequestStack(),
        );

        $file = $this->createUploadedFile('sample.csv');
        $request = Request::create('/', 'POST', [
            '_token' => 'ok',
            'file_type' => 'csv',
            'adapter' => 'bogus',
        ], [], ['file' => $file]);
        $session = $this->attachSession($requestStack, $request);

        $response = $handler->handle($request);

        self::assertNotNull($response);
        self::assertSame('/import_index', $response->headers->get('Location'));
        self::assertSame(['Nicht unterstützter Adapter: bogus'], $session->getFlashBag()->get('error'));
    }

    public function testHandleRejectsRateLimitedUploads(): void
    {
        $csrf = $this->createMock(CsrfTokenManagerInterface::class);
        $csrf->expects(self::never())->method('isTokenValid');

        $handler = $this->createHandler(
            $this->createRateLimiter([
                'allowed' => false,
                'limit' => 12,
                'remaining' => 0,
                'retry_after' => 120,
            ]),
            $this->createUploadRequestService($this->createMock(ImportManager::class)),
            $csrf,
            $requestStack = new RequestStack(),
        );

        $file = $this->createUploadedFile('sample.csv');
        $request = Request::create('/', 'POST', ['_token' => 'ok'], [], ['file' => $file]);
        $session = $this->attachSession($requestStack, $request);

        $response = $handler->handle($request);

        self::assertNotNull($response);
        self::assertSame('/import_index', $response->headers->get('Location'));
        self::assertSame(['Zu viele Uploads. Bitte warte 120 Sekunden und versuche es erneut.'], $session->getFlashBag()->get('error'));
    }

    private function createUploadedFile(string $clientName, string $mimeType = 'text/csv', string $contents = "name\nAlice\n"): UploadedFile
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'upload_valid_');
        self::assertNotFalse($tempFile);
        file_put_contents($tempFile, $contents);
        $this->cleanupFiles[] = $tempFile;

        return new UploadedFile($tempFile, $clientName, $mimeType, null, true);
    }

    private function createUrlGenerator(): UrlGeneratorInterface
    {
        $urlGenerator = $this->createMock(UrlGeneratorInterface::class);
        $urlGenerator->method('generate')
            ->willReturnCallback(static function (string $route, array $parameters = []): string {
                $path = '/' . $route;

                return $parameters === [] ? $path : $path . '?' . http_build_query($parameters);
            });

        return $urlGenerator;
    }

    private function createTranslator(): TranslatorInterface
    {
        $translator = $this->createMock(TranslatorInterface::class);
        $translator->method('trans')
            ->willReturnCallback(static function (string $id, array $parameters = []): string {
                $messages = [
                    'flash.invalid_csrf' => 'Ungültiges CSRF-Token.',
                    'upload.error.no_file_uploaded' => 'Bitte wähle eine Datei für den Import aus.',
                    'upload.error.rate_limited' => 'Zu viele Uploads. Bitte warte %seconds% Sekunden und versuche es erneut.',
                    'upload.error.unsupported_adapter' => 'Nicht unterstützter Adapter: %adapter%',
                    'upload.error.unsupported_file_type' => 'Nicht unterstützter Dateityp: %type%',
                    'upload.error.upload_failed' => 'Datei-Upload fehlgeschlagen: %reason%',
                ];

                return strtr($messages[$id] ?? $id, $parameters);
            });

        return $translator;
    }

    private function attachSession(RequestStack $requestStack, Request $request): Session
    {
        $session = new Session(new MockArraySessionStorage());
        $session->start();
        $request->setSession($session);
        $requestStack->push($request);

        return $session;
    }

    /**
     * @return array{allowed: true, limit: int, remaining: int, retry_after: int}
     */
    private function allowedRateLimit(): array
    {
        return [
            'allowed' => true,
            'limit' => 12,
            'remaining' => 11,
            'retry_after' => 0,
        ];
    }

    private function createUploadRequestService(ImportManager $importManager): ImportUploadRequestService
    {
        return new ImportUploadRequestService(
            $importManager,
            new ImportUploadRequestValidator(Validation::createValidator(), $importManager),
        );
    }

    /**
     * @param array{allowed: bool, limit: int, remaining: int, retry_after: int} $result
     */
    private function createRateLimiter(array $result): ImportRateLimiter
    {
        $rateLimiter = $this->createMock(ImportRateLimiter::class);
        $rateLimiter->method('consumeWebUpload')->willReturn($result);

        return $rateLimiter;
    }

    private function createHandler(
        ImportRateLimiter $rateLimiter,
        ImportUploadRequestService $uploadRequestService,
        CsrfTokenManagerInterface $csrf,
        RequestStack $requestStack,
    ): FileUploadHandler {
        $translator = $this->createTranslator();
        $responder = new ImportStepResponder(
            $this->createMock(Environment::class),
            $this->createUrlGenerator(),
            $this->createMock(ImportManager::class),
        );

        return new FileUploadHandler(
            new WebUploadFlow(
                $rateLimiter,
                $uploadRequestService,
                $csrf,
                new ImportFlashMessenger($requestStack, $translator),
                $responder,
            ),
        );
    }
}
