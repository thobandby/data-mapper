<?php

declare(strict_types=1);

namespace DynamicDataImporter\Cli\Infrastructure\Filesystem;

use DynamicDataImporter\Cli\Exception\CliUsageException;

final class CliArtifactDirectoryEnsurer
{
    public function ensure(string $directory): void
    {
        if (! is_dir($directory) && ! mkdir($directory, 0777, true) && ! is_dir($directory)) {
            throw new CliUsageException(\sprintf('Directory "%s" could not be created.', $directory));
        }
    }
}
