<?php

declare(strict_types=1);

namespace DynamicDataImporter\Cli\Output;

use DynamicDataImporter\Cli\Contract\OutputFormatterInterface;

final class CliDeferredJsonEncoder
{
    private ?OutputFormatterInterface $formatter = null;

    public function bindFormatter(OutputFormatterInterface $formatter): void
    {
        $this->formatter = $formatter;
    }

    public function encode(mixed $value): string
    {
        if ($this->formatter === null) {
            throw new \LogicException('CLI output formatter has not been initialized.');
        }

        return $this->formatter->encodeJson($value);
    }
}
