<?php

declare(strict_types=1);

use App\Kernel;

require_once dirname(__DIR__) . '/../../vendor/autoload.php';

$env = $_ENV['APP_ENV'] ?? 'dev';
$debug = (bool) ($_ENV['APP_DEBUG'] ?? ($env !== 'prod'));

$request = Symfony\Component\HttpFoundation\Request::createFromGlobals();
$kernel = new Kernel($env, $debug);
$response = $kernel->handle($request);
$response->send();
$kernel->terminate($request, $response);
