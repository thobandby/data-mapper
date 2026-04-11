<?php

declare(strict_types=1);

namespace DynamicDataImporter\Infrastructure\Api;

final readonly class ApiServerDataReader
{
    private ApiServerValueReader $valueReader;

    /**
     * @param array<string, mixed> $server
     */
    public function __construct(array $server)
    {
        $this->valueReader = new ApiServerValueReader($server);
    }

    public function requestUri(): string
    {
        $requestUri = parse_url($this->valueReader->string('REQUEST_URI', '/'), \PHP_URL_PATH);

        return is_string($requestUri) && $requestUri !== '' ? $requestUri : '/';
    }

    public function requestMethod(): string
    {
        return $this->valueReader->string('REQUEST_METHOD', 'GET');
    }

    public function origin(): ?string
    {
        return $this->valueReader->nullableString('HTTP_ORIGIN');
    }
}
