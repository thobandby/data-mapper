<?php

declare(strict_types=1);

require_once __DIR__ . '/../../../../vendor/autoload.php';

use DynamicDataImporter\Application\Service\ImportWorkflowService;
use DynamicDataImporter\Application\UseCase\AnalyzeFile;
use DynamicDataImporter\Infrastructure\Api\ApiApplication;
use DynamicDataImporter\Infrastructure\Api\ApiFileUploadReader;

$server = filter_input_array(INPUT_SERVER);
if (! is_array($server)) {
    $server = [];
}

$post = filter_input_array(INPUT_POST);
if (! is_array($post)) {
    $post = [];
}

$files = (new ApiFileUploadReader())->read();

(new ApiApplication(
    new ImportWorkflowService(new AnalyzeFile()),
    $server,
    $post,
    $files,
))->run();
