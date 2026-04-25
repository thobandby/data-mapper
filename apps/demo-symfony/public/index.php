<?php

declare(strict_types=1);

use App\Kernel;
use Symfony\Component\HttpFoundation\Request;

require_once dirname(__DIR__) . '/../../vendor/autoload.php';

$env = $_ENV['APP_ENV'] ?? 'dev';
$debug = (bool) ($_ENV['APP_DEBUG'] ?? ($env !== 'prod'));

$trustedProxies = array_values(array_filter(array_map(
    static fn (string $proxy): string => trim($proxy),
    explode(',', (string) ($_ENV['TRUSTED_PROXIES'] ?? $_SERVER['TRUSTED_PROXIES'] ?? ''))
)));

if ($trustedProxies !== []) {
    Request::setTrustedProxies(
        $trustedProxies,
        Request::HEADER_X_FORWARDED_FOR
        | Request::HEADER_X_FORWARDED_HOST
        | Request::HEADER_X_FORWARDED_PROTO
        | Request::HEADER_X_FORWARDED_PORT
        | Request::HEADER_X_FORWARDED_PREFIX
    );
}

$trustedHosts = array_values(array_filter(array_map(
    static fn (string $host): string => trim($host),
    explode(',', (string) ($_ENV['TRUSTED_HOSTS'] ?? $_SERVER['TRUSTED_HOSTS'] ?? ''))
)));

if ($trustedHosts !== []) {
    Request::setTrustedHosts($trustedHosts);
}

$request = Request::createFromGlobals();
$kernel = new Kernel($env, $debug);
$response = $kernel->handle($request);
$response->send();
$kernel->terminate($request, $response);
