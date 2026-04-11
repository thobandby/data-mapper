<?php

declare(strict_types=1);

namespace App\Import\Status;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\Table;

final class ImportJobSchemaManager
{
    private const TABLE_NAME = 'import_jobs';

    private bool $schemaReady = false;

    public function __construct(
        private readonly Connection $connection,
    ) {
    }

    public function ensureSchema(): void
    {
        if ($this->schemaReady) {
            return;
        }

        $schemaManager = $this->connection->createSchemaManager();
        if (! $schemaManager->tablesExist([self::TABLE_NAME])) {
            $table = new Table(self::TABLE_NAME);
            $table->addColumn('id', 'string', ['length' => 64]);
            $table->addColumn('status', 'string', ['length' => 32]);
            $table->addColumn('job_payload', 'text');
            $table->addColumn('created_at', 'string', ['length' => 19]);
            $table->addColumn('updated_at', 'string', ['length' => 19]);
            $table->setPrimaryKey(['id']);
            $table->addIndex(['status', 'updated_at'], 'idx_import_jobs_status_updated_at');

            $schemaManager->createTable($table);
        }

        $this->schemaReady = true;
    }
}
