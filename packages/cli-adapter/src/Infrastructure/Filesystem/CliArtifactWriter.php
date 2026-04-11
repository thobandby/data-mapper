<?php

declare(strict_types=1);

namespace DynamicDataImporter\Cli\Infrastructure\Filesystem;

use DynamicDataImporter\Cli\Contract\ArtifactWriterInterface;
use DynamicDataImporter\Cli\Input\CliOptions;

final readonly class CliArtifactWriter implements ArtifactWriterInterface
{
    public function __construct(
        private CliArtifactContentBuilder $contentBuilder,
        private CliArtifactDirectoryEnsurer $directoryEnsurer,
    ) {
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function write(array $payload, CliOptions $options): void
    {
        if ($options->action !== 'execute' || $options->writeOutputPath === null) {
            return;
        }

        $this->directoryEnsurer->ensure(dirname($options->writeOutputPath));
        file_put_contents($options->writeOutputPath, $this->contentBuilder->build($payload));
    }
}
