<?php

declare(strict_types=1);

namespace App\Tests;

use App\Service\ImportRateLimiter;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\RateLimiter\Storage\CacheStorage;

final class ImportRateLimiterTest extends TestCase
{
    public function testConsumeRateLimitIsDisabledInTestEnvironment(): void
    {
        $factory = new RateLimiterFactory([
            'id' => 'test',
            'policy' => 'fixed_window',
            'limit' => 5,
            'interval' => '1 minute',
        ], new CacheStorage(new ArrayAdapter()));
        $limiter = new ImportRateLimiter($factory, $factory, $factory, 'test');

        self::assertSame([
            'allowed' => true,
            'limit' => 0,
            'remaining' => 0,
            'retry_after' => 0,
        ], $limiter->consumeApiImportStart('127.0.0.1'));
    }
}
