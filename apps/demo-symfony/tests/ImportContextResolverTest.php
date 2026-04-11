<?php

declare(strict_types=1);

namespace App\Tests;

use App\Import\Http\ImportContextResolver;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

final class ImportContextResolverTest extends TestCase
{
    private ImportContextResolver $resolver;

    protected function setUp(): void
    {
        $this->resolver = new ImportContextResolver();
    }

    public function testResolvePrefersRequestPayloadOverQueryParameters(): void
    {
        $request = Request::create('/import/process?file=query.csv&adapter=memory&file_type=json&table=query_rows&delimiter=%3B', 'POST', [
            'file' => 'body.csv',
            'adapter' => 'json',
            'file_type' => 'csv',
            'table' => 'request_rows',
            'delimiter' => ',',
            'mapping' => ['first_name' => 'name'],
            'target_columns' => ['name', 'email'],
        ]);

        $context = $this->resolver->resolve($request);

        self::assertSame('query.csv', $context->file);
        self::assertSame('memory', $context->adapter);
        self::assertSame('json', $context->fileType);
        self::assertSame('query_rows', $context->tableName);
        self::assertSame(';', $context->delimiter);
        self::assertSame(['first_name' => 'name'], $context->mapping);
        self::assertSame(['name', 'email'], $context->targetColumns);
    }

    public function testResolveFallsBackToDefaultsAndQueryCollections(): void
    {
        $request = Request::create('/import/mapping?mapping%5Bsource%5D=target&target_columns%5B0%5D=id', 'GET');

        $context = $this->resolver->resolve($request);

        self::assertSame('', $context->file);
        self::assertSame('memory', $context->adapter);
        self::assertSame('', $context->fileType);
        self::assertSame('imported_rows', $context->tableName);
        self::assertNull($context->delimiter);
        self::assertSame(['source' => 'target'], $context->mapping);
        self::assertSame(['id'], $context->targetColumns);
    }

    public function testResolveSanitizesAndDefaultsTableName(): void
    {
        $request = Request::create('/import/schema?table=orders-2026/../', 'GET');

        $context = $this->resolver->resolve($request);

        self::assertSame('orders2026', $context->tableName);
    }

    public function testResolveUsesDefaultTableNameWhenSanitizationRemovesEverything(): void
    {
        $request = Request::create('/import/schema', 'POST', [
            'new_table_name' => '!!!',
        ]);

        $context = $this->resolver->resolve($request, 'new_table_name');

        self::assertSame('imported_rows', $context->tableName);
    }
}
