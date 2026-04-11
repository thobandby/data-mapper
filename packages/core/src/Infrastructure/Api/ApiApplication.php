<?php

declare(strict_types=1);

namespace DynamicDataImporter\Infrastructure\Api;

use DynamicDataImporter\Application\Service\ImportWorkflowService;

final readonly class ApiApplication
{
    private const SWAGGER_UI_HTML = <<<'HTML'
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Swagger UI</title>
    <link rel="stylesheet" type="text/css" href="https://unpkg.com/swagger-ui-dist@5/swagger-ui.css">
    <link rel="icon" type="image/png" href="https://unpkg.com/swagger-ui-dist@5/favicon-32x32.png" sizes="32x32">
</head>
<body>
    <div id="swagger-ui"></div>
    <script src="https://unpkg.com/swagger-ui-dist@5/swagger-ui-bundle.js"></script>
    <script>
        window.onload = function() {
            window.ui = SwaggerUIBundle({
                url: "/openapi.yaml",
                dom_id: '#swagger-ui',
                deepLinking: true,
                presets: [
                    SwaggerUIBundle.presets.apis,
                    SwaggerUIBundle.SwaggerUIStandalonePreset
                ],
                layout: "BaseLayout"
            });
        };
    </script>
</body>
</html>
HTML;

    private ApiRequest $request;
    private ApiResponder $responder;
    private ApiRouteDispatcher $routeDispatcher;

    /**
     * @param array<string, mixed> $server
     * @param array<string, mixed> $post
     * @param array<string, mixed> $files
     */
    public function __construct(
        private ImportWorkflowService $workflow,
        array $server,
        array $post,
        array $files,
    ) {
        $this->request = new ApiRequest($server, $post, $files);
        $this->responder = new ApiResponder();
        $this->routeDispatcher = new ApiRouteDispatcher(
            new ApiImportActionHandler($this->workflow, $this->request, $this->responder),
            $this->responder,
        );
    }

    public function run(): void
    {
        $requestUri = $this->request->resolveRequestUri();
        $method = $this->request->resolveRequestMethod();

        $this->responder->sendCorsHeaders($this->request->origin());

        if ($method === 'OPTIONS') {
            return;
        }

        $this->routeDispatcher->dispatch(
            $requestUri,
            $method,
            $this->renderSwaggerUi(),
            __DIR__ . '/../../../docs/openapi.yaml',
        );
    }

    private function renderSwaggerUi(): string
    {
        return self::SWAGGER_UI_HTML;
    }
}
