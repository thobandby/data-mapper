<?php

declare(strict_types=1);

namespace DynamicDataImporter\Cli\Contract;

use DynamicDataImporter\Cli\Input\CliOptions;

interface WorkflowExecutorInterface
{
    /**
     * @return array<string, mixed>
     */
    public function analyze(CliOptions $options): array;

    /**
     * @return array<string, mixed>
     */
    public function preview(CliOptions $options): array;

    /**
     * @param callable(int): void $progressCallback
     *
     * @return array<string, mixed>
     */
    public function execute(CliOptions $options, callable $progressCallback): array;
}
