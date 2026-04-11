<?php

declare(strict_types=1);

namespace DynamicDataImporter\Cli\Contract;

use DynamicDataImporter\Cli\Input\CliOptions;

interface WizardInterface
{
    public function run(): CliOptions;
}
