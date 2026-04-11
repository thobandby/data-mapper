<?php

declare(strict_types=1);

namespace App\Service;

final class ImportArtifactStorage
{
    public function __construct(
        private readonly string $artifactDirectory,
    ) {
    }

    public function allocatePath(string $extension): string
    {
        $this->ensureDirectory();

        return sprintf(
            '%s/import_result_%s.%s',
            $this->artifactDirectory,
            bin2hex(random_bytes(16)),
            ltrim($extension, '.'),
        );
    }

    public function deleteIfExists(?string $path): void
    {
        if ($this->isManagedPath($path) && is_file($path)) {
            unlink($path);
        }
    }

    public function isManagedPath(?string $path): bool
    {
        if ($path === null || $path === '') {
            return false;
        }

        $directory = realpath($this->artifactDirectory);
        $candidate = realpath($path);

        return $directory !== false
            && $candidate !== false
            && str_starts_with($candidate, $directory . DIRECTORY_SEPARATOR);
    }

    public function directory(): string
    {
        $this->ensureDirectory();

        return $this->artifactDirectory;
    }

    private function ensureDirectory(): void
    {
        if (! is_dir($this->artifactDirectory)) {
            mkdir($this->artifactDirectory, 0o777, true);
        }
    }
}
