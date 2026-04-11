<?php

declare(strict_types=1);

namespace DynamicDataImporter\Tests\Infrastructure\Api;

use DynamicDataImporter\Infrastructure\Api\ApiServerCommandFactory;
use PHPUnit\Framework\TestCase;

final class ApiServerCommandFactoryTest extends TestCase
{
    public function testResolvePortReturnsDefaultForMissingValue(): void
    {
        $factory = new ApiServerCommandFactory();

        self::assertSame(8000, $factory->resolvePort(null));
        self::assertSame(8000, $factory->resolvePort(''));
    }

    public function testResolvePortAcceptsNumericStringWithinRange(): void
    {
        $factory = new ApiServerCommandFactory();

        self::assertSame(8080, $factory->resolvePort('8080'));
    }

    public function testResolvePortRejectsInvalidInput(): void
    {
        $factory = new ApiServerCommandFactory();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Port must be an integer between 1 and 65535.');

        $factory->resolvePort('8080; touch /tmp/pwned');
    }

    public function testCreateCommandBuildsShellFreeArgumentList(): void
    {
        $factory = new ApiServerCommandFactory();

        self::assertSame(
            [PHP_BINARY, '-S', '0.0.0.0:8080', '/tmp/api-index.php'],
            $factory->createCommand(8080, '/tmp/api-index.php'),
        );
    }
}
