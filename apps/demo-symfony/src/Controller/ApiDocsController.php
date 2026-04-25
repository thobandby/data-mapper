<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ApiDocsController extends AbstractController
{
    #[Route('/api/docs.json', name: 'api_docs_json', methods: ['GET'])]
    public function openApi(): JsonResponse
    {
        return $this->json($this->openApiDocument());
    }

    #[Route('/api/docs', name: 'api_docs', methods: ['GET'])]
    public function swaggerUi(): Response
    {
        return new Response($this->swaggerUiHtml());
    }

    /**
     * @return array<string, mixed>
     */
    private function openApiDocument(): array
    {
        return [
            'openapi' => '3.1.0',
            'info' => [
                'title' => 'Dynamic Data Importer Demo API',
                'version' => '1.0.0',
                'description' => $this->apiInfoDescription(),
            ],
            'paths' => [
                '/api/imports' => [
                    'post' => [
                        'summary' => 'Queue an import job',
                        'description' => $this->queueImportDescription(),
                        'requestBody' => [
                            'required' => true,
                            'content' => [
                                'multipart/form-data' => [
                                    'schema' => [
                                        'type' => 'object',
                                        'required' => ['file'],
                                        'properties' => [
                                            'file' => [
                                                'type' => 'string',
                                                'format' => 'binary',
                                                'description' => 'Upload a CSV, XLS, XLSX, JSON, or XML file up to 10 MB.',
                                            ],
                                            'file_type' => ['type' => 'string', 'example' => 'csv'],
                                            'adapter' => [
                                                'type' => 'string',
                                                'example' => 'symfony',
                                                'description' => $this->adapterDescription(),
                                            ],
                                            'table_name' => ['type' => 'string', 'example' => 'imported_rows'],
                                            'delimiter' => [
                                                'type' => 'string',
                                                'example' => ',',
                                                'description' => $this->delimiterDescription(),
                                            ],
                                            'mapping' => [
                                                'type' => 'string',
                                                'description' => 'JSON object encoded as string, for example {"name":"full_name"}',
                                                'example' => '{"name":"full_name","age":"years","email":"contact_email"}',
                                            ],
                                        ],
                                    ],
                                    'encoding' => [
                                        'mapping' => [
                                            'contentType' => 'application/json',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                        'responses' => [
                            '202' => [
                                'description' => 'Import job queued',
                                'content' => [
                                    'application/json' => [
                                        'example' => [
                                            'job_id' => 'e6f2d2e1b7b7441cad0d4c6d4dbaf284',
                                            'status' => 'queued',
                                            'status_url' => '/api/imports/e6f2d2e1b7b7441cad0d4c6d4dbaf284',
                                        ],
                                    ],
                                ],
                            ],
                            '400' => [
                                'description' => 'Validation failed',
                                'content' => [
                                    'application/json' => [
                                        'example' => [
                                            'error' => 'Unsupported file type: exe',
                                        ],
                                    ],
                                ],
                            ],
                            '429' => [
                                'description' => 'Rate limit exceeded',
                                'headers' => [
                                    'Retry-After' => [
                                        'schema' => ['type' => 'integer'],
                                        'description' => 'Seconds until another request is allowed.',
                                    ],
                                ],
                                'content' => [
                                    'application/json' => [
                                        'example' => [
                                            'error' => 'Too many requests. Please wait 42 seconds before trying again.',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                '/api/imports/{jobId}' => [
                    'get' => [
                        'summary' => 'Fetch import job status',
                        'parameters' => [
                            [
                                'in' => 'path',
                                'name' => 'jobId',
                                'required' => true,
                                'schema' => ['type' => 'string'],
                            ],
                        ],
                        'responses' => [
                            '200' => [
                                'description' => 'Current job status',
                                'content' => [
                                    'application/json' => [
                                        'example' => [
                                            'id' => 'e6f2d2e1b7b7441cad0d4c6d4dbaf284',
                                            'status' => 'completed',
                                            'created_at' => '2026-04-02T07:42:43+02:00',
                                            'updated_at' => '2026-04-02T07:42:45+02:00',
                                            'result' => [
                                                'processed' => 2,
                                                'imported' => 2,
                                                'errors' => [],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                            '404' => [
                                'description' => 'Job not found',
                                'content' => [
                                    'application/json' => [
                                        'example' => [
                                            'error' => 'Import job not found.',
                                        ],
                                    ],
                                ],
                            ],
                            '429' => [
                                'description' => 'Rate limit exceeded',
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    private function swaggerUiHtml(): string
    {
        return <<<HTML
        <!DOCTYPE html>
        <html lang="en">
        <head>
          <meta charset="UTF-8">
          <meta name="viewport" content="width=device-width, initial-scale=1.0">
          <title>Dynamic Data Importer API Docs</title>
          <link rel="stylesheet" href="https://unpkg.com/swagger-ui-dist@5/swagger-ui.css" />
        </head>
        <body>
          <div id="swagger-ui"></div>
          <script src="https://unpkg.com/swagger-ui-dist@5/swagger-ui-bundle.js"></script>
          <script>
            window.onload = function () {
              window.SwaggerUIBundle({
                url: '/api/docs.json',
                dom_id: '#swagger-ui',
              });
            };
          </script>
        </body>
        </html>
        HTML;
    }

    private function apiInfoDescription(): string
    {
        return implode(' ', [
            'Public demo API for queued imports. Rate-limited and intended for evaluation, not bulk ingestion.',
            'Supported file types are CSV, XLS, XLSX, JSON, and XML.',
            'Available adapters are symfony for Doctrine-based persistence via the demo app and pdo for direct PDO-based inserts.',
            'Use memory for an in-memory dry run without persisted artifacts.',
            'Use json for JSON export output, xml for XML export output, and sql for SQL script export output.',
            'The delimiter option is only relevant for CSV imports.',
            'It lets clients override automatic delimiter detection when the source file uses characters such as comma, semicolon, or tab.',
        ]);
    }

    private function queueImportDescription(): string
    {
        return implode(' ', [
            'Upload one CSV, XLS, XLSX, JSON, or XML file and queue an asynchronous import job.',
            'Supported adapters are symfony, pdo, memory, json, xml, and sql.',
            'The delimiter parameter is only used for CSV files.',
            'It overrides automatic delimiter detection, for example when a file uses semicolons instead of commas.',
            'The public demo is rate-limited and currently accepts files up to 10 MB.',
        ]);
    }

    private function adapterDescription(): string
    {
        return implode(' ', [
            'Target adapter.',
            'Use symfony to persist into the demo database via Doctrine.',
            'Use pdo for direct inserts through PDO, or memory for a dry run without persisted output.',
            'Use json for JSON export, xml for XML export, or sql for an SQL script export.',
        ]);
    }

    private function delimiterDescription(): string
    {
        return implode(' ', [
            'Optional CSV delimiter override. Only relevant for CSV uploads.',
            'Use this when the file uses a non-standard separator such as ; or \\t instead of relying on auto-detection.',
        ]);
    }
}
