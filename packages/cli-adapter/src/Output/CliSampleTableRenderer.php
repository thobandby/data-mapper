<?php

declare(strict_types=1);

namespace DynamicDataImporter\Cli\Output;

use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Output\OutputInterface;

final readonly class CliSampleTableRenderer
{
    public function __construct(
        private OutputInterface $output,
        private \Closure $stdout,
        private \Closure $encodeJson,
    ) {
    }

    /**
     * @param list<string>               $headers
     * @param list<array<string, mixed>> $sample
     */
    public function render(array $headers, array $sample): void
    {
        if ($sample === []) {
            ($this->stdout)('- (empty)' . "\n");

            return;
        }

        $table = new Table($this->output);
        $table->setHeaders($headers);

        foreach ($sample as $row) {
            $table->addRow($this->rowData($headers, $row));
        }

        $table->render();
    }

    /**
     * @param list<string>         $headers
     * @param array<string, mixed> $row
     *
     * @return list<string>
     */
    private function rowData(array $headers, array $row): array
    {
        $rowData = [];

        foreach ($headers as $header) {
            $value = $row[$header] ?? null;
            $rowData[] = \is_scalar($value) ? (string) $value : ($this->encodeJson)($value);
        }

        return $rowData;
    }
}
