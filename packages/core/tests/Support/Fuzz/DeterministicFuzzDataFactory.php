<?php

declare(strict_types=1);

namespace DynamicDataImporter\Tests\Support\Fuzz;

use DynamicDataImporter\Infrastructure\Reader\Csv\CsvOptions;

final class DeterministicFuzzDataFactory
{
    private int $state;

    public function __construct(int $seed)
    {
        $this->state = $seed !== 0 ? $seed : 1;
    }

    /**
     * @return array{
     *     content: string,
     *     options: CsvOptions,
     *     headers: list<string>,
     *     rows: list<array<string, string>>
     * }
     */
    public function csvCase(): array
    {
        $headers = $this->headers('csv');
        $rows = [];
        $rowCount = $this->nextInt(1, 8);

        for ($index = 0; $index < $rowCount; ++$index) {
            $row = [];

            foreach ($headers as $header) {
                $row[$header] = $this->csvValue();
            }

            $rows[] = $row;
        }

        $delimiter = $this->pick([',', ';']);
        $content = $this->csvLine($headers, $delimiter) . "\n";

        foreach ($rows as $row) {
            $content .= $this->csvLine(array_values($row), $delimiter) . "\n";
        }

        return [
            'content' => $content,
            'options' => new CsvOptions(delimiter: $delimiter, hasHeader: true, enclosure: '"', escape: '\\'),
            'headers' => $headers,
            'rows' => $rows,
        ];
    }

    public function malformedCsvPayload(): string
    {
        $case = $this->csvCase();
        $payloads = [
            "\"unclosed,value\nsecond,row\n",
            "col_a,col_b\none,two,three\n",
            substr($case['content'], 0, max(1, strlen($case['content']) - $this->nextInt(1, 6))),
            "name;email\nalpha\nbeta;gamma\n",
        ];

        return $this->pick($payloads);
    }

    /**
     * @return list<string>
     */
    public function adversarialCsvPayloads(): array
    {
        $longQuoted = str_repeat('segment,', 128);

        return [
            "\xEF\xBB\xBFname,email\r\nalice,test@example.com\r\n",
            "name,comment\r\nalice,\"{$longQuoted}\"\r\n",
            "name,comment\r\nalice,\"line1\r\nline2\r\nline3\"\r\n",
            "name,formula\r\nalice,=cmd|' /C calc'!A0\r\n",
        ];
    }

    /**
     * @return array{
     *     content: string,
     *     options: CsvOptions,
     *     headers: list<string>,
     *     rows: list<array<string, string>>
     * }
     */
    public function csvStressCase(): array
    {
        $headers = $this->headersWithCount('csv_stress', 8);
        $rows = [];

        for ($index = 0; $index < 40; ++$index) {
            $row = [];

            foreach ($headers as $header) {
                $row[$header] = $this->longTextValue();
            }

            $rows[] = $row;
        }

        $delimiter = $this->pick([',', ';']);
        $content = $this->csvLine($headers, $delimiter) . "\r\n";

        foreach ($rows as $row) {
            $content .= $this->csvLine(array_values($row), $delimiter) . "\r\n";
        }

        return [
            'content' => $content,
            'options' => new CsvOptions(delimiter: $delimiter, hasHeader: true, enclosure: '"', escape: '\\'),
            'headers' => $headers,
            'rows' => $rows,
        ];
    }

    /**
     * @return array{
     *     content: string,
     *     headers: list<string>,
     *     rows: list<array<string, int|float|string|bool|null>>
     * }
     */
    public function jsonCase(): array
    {
        $headers = $this->headers('json');
        $rows = [];
        $rowCount = $this->nextInt(1, 8);

        for ($index = 0; $index < $rowCount; ++$index) {
            $row = [];

            foreach ($headers as $header) {
                $row[$header] = $this->jsonValue();
            }

            $rows[] = $row;
        }

        $content = (string) json_encode($rows, \JSON_THROW_ON_ERROR);

        return [
            'content' => $content,
            'headers' => $headers,
            'rows' => $rows,
        ];
    }

    public function malformedJsonPayload(): string
    {
        $case = $this->jsonCase();
        $payloads = [
            '{invalid-json}',
            '{"mapping": }',
            substr($case['content'], 0, max(1, strlen($case['content']) - $this->nextInt(1, 5))),
            (string) json_encode('not-an-array', \JSON_THROW_ON_ERROR),
        ];

        return $this->pick($payloads);
    }

    /**
     * @return list<string>
     */
    public function adversarialJsonPayloads(): array
    {
        return [
            (string) json_encode(['not' => 'a-list'], \JSON_THROW_ON_ERROR),
            (string) json_encode([['nested', 'list']], \JSON_THROW_ON_ERROR),
            '[{"row": "' . str_repeat('x', 4096) . '"}, {"row": "' . str_repeat('y', 4096) . '"}]',
            '[{"safe":"value"},{"formula":"=1+1"},{"multiline":"line1\nline2"}]',
            '[{"broken": "\xB1"}]',
        ];
    }

    /**
     * @return array{
     *     content: string,
     *     headers: list<string>,
     *     rows: list<array<string, int|float|string|bool|null>>
     * }
     */
    public function jsonStressCase(): array
    {
        $headers = $this->headersWithCount('json_stress', 8);
        $rows = [];

        for ($index = 0; $index < 40; ++$index) {
            $row = [];

            foreach ($headers as $header) {
                $row[$header] = $this->stressJsonValue();
            }

            $rows[] = $row;
        }

        return [
            'content' => (string) json_encode($rows, \JSON_THROW_ON_ERROR),
            'headers' => $headers,
            'rows' => $rows,
        ];
    }

    /**
     * @return array{
     *     content: string,
     *     headers: list<string>,
     *     rows: list<array<string, string>>
     * }
     */
    public function xmlCase(): array
    {
        $headers = $this->headers('xml');
        $rows = [];
        $rowCount = $this->nextInt(1, 6);
        $content = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<records>\n";

        for ($index = 0; $index < $rowCount; ++$index) {
            $row = [];
            $content .= "  <record>\n";

            foreach ($headers as $header) {
                $value = $this->xmlEscapedValue();
                $row[$header] = html_entity_decode($value, \ENT_QUOTES | \ENT_XML1, 'UTF-8');
                $content .= sprintf("    <%s>%s</%s>\n", $header, $value, $header);
            }

            $content .= "  </record>\n";
            $rows[] = $row;
        }

        $content .= "</records>\n";

        return [
            'content' => $content,
            'headers' => $headers,
            'rows' => $rows,
        ];
    }

    public function malformedXmlPayload(): string
    {
        $case = $this->xmlCase();
        $payloads = [
            '<records><record><name>broken</records>',
            '<records><record attr="unterminated></record></records>',
            substr($case['content'], 0, max(1, strlen($case['content']) - $this->nextInt(1, 8))),
            '<records><record><name>A & B</name></record></records>',
        ];

        return $this->pick($payloads);
    }

    /**
     * @return list<string>
     */
    public function adversarialXmlPayloads(): array
    {
        return [
            '<!DOCTYPE records [<!ENTITY xxe SYSTEM "file:///etc/passwd">]><records><record><name>&xxe;</name></record></records>',
            '<!DOCTYPE lolz [<!ENTITY lol "lol"><!ENTITY lol1 "&lol;&lol;&lol;&lol;&lol;&lol;&lol;&lol;&lol;">]><records><record><name>&lol1;</name></record></records>',
            '<?xml version="1.0"?><records><record><payload><![CDATA[' . str_repeat('nested-text-', 256) . ']]></payload></record></records>',
            '<?xml version="1.0"?><records><record><name>alpha</name><details><inner>beta</inner><inner>gamma</inner></details></record></records>',
        ];
    }

    /**
     * @return array{
     *     content: string,
     *     headers: list<string>,
     *     rows: list<array<string, string>>
     * }
     */
    public function xmlStressCase(): array
    {
        $headers = $this->headersWithCount('xml_stress', 6);
        $rows = [];
        $content = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<records>\n";

        for ($index = 0; $index < 25; ++$index) {
            $row = [];
            $content .= "  <record>\n";

            foreach ($headers as $header) {
                $value = htmlspecialchars($this->longTextValue(), \ENT_QUOTES | \ENT_XML1, 'UTF-8');
                $row[$header] = html_entity_decode($value, \ENT_QUOTES | \ENT_XML1, 'UTF-8');
                $content .= sprintf("    <%s>%s</%s>\n", $header, $value, $header);
            }

            $content .= "  </record>\n";
            $rows[] = $row;
        }

        $content .= "</records>\n";

        return [
            'content' => $content,
            'headers' => $headers,
            'rows' => $rows,
        ];
    }

    /**
     * @return array{
     *     headers: list<string>,
     *     rows: list<array<string, float|int|string|null>>
     * }
     */
    public function spreadsheetCase(): array
    {
        $headers = $this->headers('sheet');
        $rows = [];
        $rowCount = $this->nextInt(1, 8);

        for ($index = 0; $index < $rowCount; ++$index) {
            $row = [];

            foreach ($headers as $header) {
                $row[$header] = $this->spreadsheetValue();
            }

            $rows[] = $row;
        }

        return [
            'headers' => $headers,
            'rows' => $rows,
        ];
    }

    /**
     * @return array{
     *     headers: list<string>,
     *     rows: list<array<string, float|int|string|null>>
     * }
     */
    public function spreadsheetStressCase(): array
    {
        $headers = $this->headersWithCount('sheet_stress', 10);
        $rows = [];

        for ($index = 0; $index < 50; ++$index) {
            $row = [];

            foreach ($headers as $header) {
                $row[$header] = $this->stressSpreadsheetValue();
            }

            $rows[] = $row;
        }

        return [
            'headers' => $headers,
            'rows' => $rows,
        ];
    }

    /**
     * @return array{
     *     mapping: array<string, string>,
     *     rowData: array<string, int|float|string|bool|null>,
     *     headers: list<string>,
     *     expectedData: array<string, int|float|string|bool|null>,
     *     expectedHeaders: list<string>
     * }
     */
    public function mappingTransformerCase(): array
    {
        $sourceHeaders = $this->headersWithCount('map_src', $this->nextInt(3, 8));
        $mapping = [];
        $rowData = [];
        $expectedData = [];
        $expectedHeaders = [];
        $usedTargets = [];

        foreach ($sourceHeaders as $header) {
            $mappedHeader = match ($this->nextInt(0, 4)) {
                0 => '',
                1, 2 => $this->uniqueMappedHeader($usedTargets),
                default => $header,
            };

            $mapping[$header] = $mappedHeader;

            $value = $this->stressJsonValue();
            $rowData[$header] = $value;

            if ($mappedHeader === '') {
                continue;
            }

            $expectedData[$mappedHeader] = $value;
            $expectedHeaders[] = $mappedHeader;
        }

        return [
            'mapping' => $mapping,
            'rowData' => $rowData,
            'headers' => $sourceHeaders,
            'expectedData' => $expectedData,
            'expectedHeaders' => $expectedHeaders,
        ];
    }

    /**
     * @return array{
     *     mapping: array<string, string>,
     *     rowData: array<string, string>,
     *     headers: list<string>
     * }
     */
    public function mappingTransformerCollisionCase(): array
    {
        $target = 'shared_' . $this->alphaNumericString(6);
        $sourceA = 'source_' . $this->alphaNumericString(6);
        $sourceB = 'source_' . $this->alphaNumericString(6);

        return [
            'mapping' => [
                $sourceA => $target,
                $sourceB => $target,
            ],
            'rowData' => [
                $sourceA => 'alpha',
                $sourceB => 'beta',
            ],
            'headers' => [$sourceA, $sourceB],
        ];
    }

    /**
     * @return array{
     *     mapping: array<string, string>,
     *     json: string
     * }
     */
    public function stringMappingJsonCase(): array
    {
        $entryCount = $this->nextInt(2, 8);
        $mapping = [];

        for ($index = 0; $index < $entryCount; ++$index) {
            $mapping['source_' . $this->alphaNumericString(6)] = 'target_' . $this->alphaNumericString(6);
        }

        return [
            'mapping' => $mapping,
            'json' => (string) json_encode($mapping, \JSON_THROW_ON_ERROR),
        ];
    }

    /**
     * @return list<string>
     */
    public function invalidMappingJsonPayloads(): array
    {
        return [
            '{invalid}',
            '"string-root"',
            '[{"source":"target"}]',
            '{"name":1}',
            '{"name":""}',
            '{"name":null}',
            '{' . str_repeat('"bad":', 20),
        ];
    }

    /**
     * @return array{
     *     mapping: array<string, string>,
     *     json: string
     * }
     */
    public function stressStringMappingJsonCase(): array
    {
        $mapping = [];

        for ($index = 0; $index < 40; ++$index) {
            $mapping['source_' . $this->alphaNumericString(12)] = 'target_' . $this->alphaNumericString(18);
        }

        return [
            'mapping' => $mapping,
            'json' => (string) json_encode($mapping, \JSON_THROW_ON_ERROR),
        ];
    }

    /**
     * @return list<string>
     */
    private function headers(string $prefix): array
    {
        $count = $this->nextInt(2, 5);

        return $this->headersWithCount($prefix, $count);
    }

    /**
     * @return list<string>
     */
    private function headersWithCount(string $prefix, int $count): array
    {
        $headers = [];

        for ($index = 0; $index < $count; ++$index) {
            $headers[] = sprintf('%s_%d_%d', $prefix, $index + 1, $this->nextInt(10, 99));
        }

        return $headers;
    }

    private function csvValue(): string
    {
        $base = $this->alphaNumericString($this->nextInt(3, 10));
        $suffixes = [
            '',
            ' ' . $this->alphaNumericString($this->nextInt(2, 6)),
            ',' . $this->alphaNumericString($this->nextInt(2, 4)),
            ';' . $this->alphaNumericString($this->nextInt(2, 4)),
            ' "' . $this->alphaNumericString($this->nextInt(2, 4)) . '"',
        ];

        return $base . $this->pick($suffixes);
    }

    private function jsonValue(): int|float|string|bool|null
    {
        return match ($this->nextInt(0, 5)) {
            0 => $this->nextInt(-500, 500),
            1 => $this->nextInt(-500, 500) / 10,
            2 => $this->alphaNumericString($this->nextInt(3, 12)),
            3 => $this->nextInt(0, 1) === 1,
            4 => null,
            default => $this->alphaNumericString($this->nextInt(2, 5)) . ' ' . $this->alphaNumericString($this->nextInt(2, 5)),
        };
    }

    private function xmlEscapedValue(): string
    {
        $raw = $this->alphaNumericString($this->nextInt(3, 10)) . $this->pick(['', ' & more', ' < less', ' "quote"']);

        return htmlspecialchars($raw, \ENT_QUOTES | \ENT_XML1, 'UTF-8');
    }

    private function spreadsheetValue(): float|int|string|null
    {
        return match ($this->nextInt(0, 4)) {
            0 => $this->nextInt(-500, 500),
            1 => $this->nextInt(-500, 500) / 10,
            2 => $this->alphaNumericString($this->nextInt(3, 12)),
            3 => null,
            default => $this->alphaNumericString($this->nextInt(2, 5)) . ' ' . $this->alphaNumericString($this->nextInt(2, 5)),
        };
    }

    private function stressJsonValue(): int|float|string|bool|null
    {
        return match ($this->nextInt(0, 5)) {
            0 => $this->nextInt(-5000, 5000),
            1 => $this->nextInt(-5000, 5000) / 100,
            2 => $this->longTextValue(),
            3 => $this->nextInt(0, 1) === 1,
            4 => null,
            default => '=SUM(' . $this->nextInt(1, 9) . ':' . $this->nextInt(10, 99) . ')',
        };
    }

    private function stressSpreadsheetValue(): float|int|string|null
    {
        return match ($this->nextInt(0, 4)) {
            0 => $this->nextInt(-5000, 5000),
            1 => $this->nextInt(-5000, 5000) / 100,
            2 => $this->longTextValue(),
            3 => null,
            default => '@literal-' . $this->alphaNumericString(10),
        };
    }

    private function longTextValue(): string
    {
        return implode(
            ' ',
            [
                $this->alphaNumericString(24),
                $this->alphaNumericString(24),
                $this->alphaNumericString(24),
                '&',
                $this->alphaNumericString(12),
            ],
        );
    }

    /**
     * @param array<string, true> $usedTargets
     */
    private function uniqueMappedHeader(array &$usedTargets): string
    {
        do {
            $candidate = 'mapped_' . $this->alphaNumericString(8);
        } while (isset($usedTargets[$candidate]));

        $usedTargets[$candidate] = true;

        return $candidate;
    }

    /**
     * @param list<string> $values
     */
    private function csvLine(array $values, string $delimiter): string
    {
        $escaped = array_map(
            static function (string $value) use ($delimiter): string {
                $needsQuotes = str_contains($value, $delimiter)
                    || str_contains($value, '"')
                    || str_contains($value, "\n")
                    || str_contains($value, "\r");

                $value = str_replace('"', '""', $value);

                return $needsQuotes ? '"' . $value . '"' : $value;
            },
            $values,
        );

        return implode($delimiter, $escaped);
    }

    private function alphaNumericString(int $length): string
    {
        $alphabet = 'abcdefghijklmnopqrstuvwxyz0123456789';
        $value = '';

        for ($index = 0; $index < $length; ++$index) {
            $value .= $alphabet[$this->nextInt(0, strlen($alphabet) - 1)];
        }

        return $value;
    }

    /**
     * @template T
     *
     * @param list<T> $values
     *
     * @return T
     */
    private function pick(array $values): mixed
    {
        return $values[$this->nextInt(0, count($values) - 1)];
    }

    private function nextInt(int $min, int $max): int
    {
        $this->state = (1103515245 * $this->state + 12345) & 0x7FFFFFFF;

        return $min + ($this->state % (($max - $min) + 1));
    }
}
