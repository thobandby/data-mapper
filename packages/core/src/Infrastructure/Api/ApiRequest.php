<?php

declare(strict_types=1);

namespace DynamicDataImporter\Infrastructure\Api;

final readonly class ApiRequest
{
    /** @var array<string, mixed> */
    private array $post;

    /** @var array<string, mixed> */
    private array $files;

    private ApiServerDataReader $serverDataReader;
    private ApiUploadResolver $uploadResolver;
    private ApiMappingDecoder $mappingDecoder;
    private ApiPostDataReader $postDataReader;

    /**
     * @param array<string, mixed> $server
     * @param array<string, mixed> $post
     * @param array<string, mixed> $files
     */
    public function __construct(
        array $server,
        array $post,
        array $files,
    ) {
        $this->post = $post;
        $this->serverDataReader = new ApiServerDataReader($server);
        $this->uploadResolver = new ApiUploadResolver();
        $this->mappingDecoder = new ApiMappingDecoder();
        $this->postDataReader = new ApiPostDataReader($post);
        $this->files = $files;
    }

    public function resolveRequestUri(): string
    {
        return $this->serverDataReader->requestUri();
    }

    public function resolveRequestMethod(): string
    {
        return $this->serverDataReader->requestMethod();
    }

    public function origin(): ?string
    {
        return $this->serverDataReader->origin();
    }

    /**
     * @return array{tmp_name: string, file_type: string}
     */
    public function requireUpload(): array
    {
        return $this->uploadResolver->requireUpload($this->files);
    }

    /**
     * @return array<string, string>
     */
    public function requestMapping(): array
    {
        return $this->mappingDecoder->decode($this->post);
    }

    public function requestString(string $key, string $default = ''): string
    {
        return $this->postDataReader->string($key, $default);
    }

    public function requestNullableString(string $key): ?string
    {
        return $this->postDataReader->nullableString($key);
    }

    public function requestInt(string $key, int $default): int
    {
        return $this->postDataReader->int($key, $default);
    }
}
