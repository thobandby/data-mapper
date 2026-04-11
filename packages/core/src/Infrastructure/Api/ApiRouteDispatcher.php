<?php

declare(strict_types=1);

namespace DynamicDataImporter\Infrastructure\Api;

final readonly class ApiRouteDispatcher
{
    public function __construct(
        private ApiImportActionHandler $actionHandler,
        private ApiResponder $responder,
    ) {
    }

    public function dispatch(string $requestUri, string $method, string $swaggerUiHtml, string $openApiPath): void
    {
        if ($requestUri === '/openapi.yaml') {
            $this->responder->respondWithOpenApi($openApiPath);

            return;
        }

        if ($requestUri === '/' || $requestUri === '/index.html') {
            $this->responder->respondWithSwaggerUi($swaggerUiHtml);

            return;
        }

        if ($method !== 'POST') {
            $this->responder->jsonResponse(['error' => 'Not Found'], 404);

            return;
        }

        match ($requestUri) {
            '/imports/analyze', '/analyze' => $this->actionHandler->analyze(),
            '/imports/preview' => $this->actionHandler->preview(),
            '/imports/execute' => $this->actionHandler->execute(false),
            '/import/sql' => $this->actionHandler->execute(true),
            default => $this->responder->jsonResponse(['error' => 'Not Found'], 404),
        };
    }
}
