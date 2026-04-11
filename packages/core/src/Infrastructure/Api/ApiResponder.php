<?php

declare(strict_types=1);

namespace DynamicDataImporter\Infrastructure\Api;

final readonly class ApiResponder
{
    private const ALLOWED_CORS_HOSTS = ['localhost', '127.0.0.1'];

    public function sendCorsHeaders(?string $origin): void
    {
        if ($this->isAllowedOrigin($origin)) {
            header(sprintf('Access-Control-Allow-Origin: %s', $origin));
            header('Vary: Origin');
        }

        header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type');
    }

    public function respondWithOpenApi(string $path): void
    {
        header('Content-Type: text/yaml');
        echo file_get_contents($path);
    }

    public function respondWithSwaggerUi(string $html): void
    {
        header('Content-Type: text/html');
        echo $html;
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function jsonResponse(array $payload, int $statusCode = 200): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }

    public function errorResponse(\Throwable $e): void
    {
        $this->jsonResponse(['error' => $e->getMessage()], 400);
    }

    private function isAllowedOrigin(?string $origin): bool
    {
        if ($origin === null) {
            return false;
        }

        $host = parse_url($origin, PHP_URL_HOST);

        return is_string($host) && in_array($host, self::ALLOWED_CORS_HOSTS, true);
    }
}
