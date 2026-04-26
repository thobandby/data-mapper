const { spawn } = require('node:child_process');
const fs = require('node:fs');
const os = require('node:os');
const path = require('node:path');
const net = require('node:net');
const assert = require('node:assert/strict');
const { chromium } = require('playwright');

const HOST = '127.0.0.1';
const PROJECT_ROOT = path.resolve(__dirname, '..');
const PUBLIC_DIR = path.join(PROJECT_ROOT, 'apps', 'demo-symfony', 'public');
const CONSOLE = path.join(PROJECT_ROOT, 'apps', 'demo-symfony', 'bin', 'console');
const SAMPLE_FILE = path.join(PROJECT_ROOT, 'apps', 'demo-symfony', 'data', 'sample.csv');
const SAMPLE_XML_FILE = path.join(PROJECT_ROOT, 'apps', 'demo-symfony', 'data', 'sample.xml');
const CORRUPTED_CSV_FILE = path.join(PROJECT_ROOT, 'packages', 'core', 'tests', 'data', 'csv_invalid_unescaped_semicolon.csv');
const CORRUPTED_JSON_FILE = path.join(PROJECT_ROOT, 'packages', 'core', 'tests', 'data', 'json_invalid_missing_comma.json');
const SCENARIO_FILTER = process.env.E2E_SCENARIO ?? '';

async function main() {
  assertFileExists(SAMPLE_FILE, 'Sample CSV file not found.');
  assertFileExists(SAMPLE_XML_FILE, 'Sample XML file not found.');
  assertFileExists(CORRUPTED_CSV_FILE, 'Corrupted CSV fixture not found.');
  assertFileExists(CORRUPTED_JSON_FILE, 'Corrupted JSON fixture not found.');
  assertFileExists(CONSOLE, 'Symfony console not found.');

  const tempDir = fs.mkdtempSync(path.join(os.tmpdir(), 'import-wizard-e2e-'));
  const phpTempDir = path.join(tempDir, 'php-tmp');
  const databasePath = path.join(tempDir, 'demo.sqlite');
  const databaseUrl = `sqlite:///${databasePath}`;
  fs.mkdirSync(phpTempDir, { recursive: true });
  const cleanupPaths = [tempDir];
  const scenarios = createScenarios(tempDir)
    .filter((scenario) => SCENARIO_FILTER === '' || scenario.name === SCENARIO_FILTER);
  const port = await getAvailablePort();
  const baseUrl = `http://${HOST}:${port}`;

  const server = await startPhpServer(port, phpTempDir, databaseUrl);

  let browser;
  try {
    browser = await chromium.launch({
      headless: true,
      args: ['--no-sandbox', '--disable-dev-shm-usage'],
    });

    for (const scenario of scenarios) {
      console.log(`Running scenario: ${scenario.name}`);

      const context = await browser.newContext({
        acceptDownloads: true,
      });

      try {
        const page = await context.newPage();
        try {
          await runScenario(page, scenario, baseUrl);
        } catch (error) {
          const wizardText = normalizeCellText(await page.locator('turbo-frame#import_step').textContent().catch(() => ''));
          const pageAlerts = normalizeCellText(await page.locator('.alert').allTextContents().then((texts) => texts.join(' ')).catch(() => ''));
          const headings = normalizeCellText(await page.locator('h1, h2, h3').allTextContents().then((texts) => texts.join(' | ')).catch(() => ''));
          error.message = `Scenario "${scenario.name}" failed: ${error.message} URL: ${page.url()} Headings: ${headings} Wizard text: ${wizardText} Alerts: ${pageAlerts}`;
          throw error;
        }
      } finally {
        await context.close();
      }
    }

    if (SCENARIO_FILTER === '' || SCENARIO_FILTER === 'api-docs-and-import-flow') {
      console.log('Running scenario: api-docs-and-import-flow');
      await runApiScenario(baseUrl, phpTempDir, databaseUrl);
    }

    const verifiedScenarios = scenarios.length + (SCENARIO_FILTER === '' || SCENARIO_FILTER === 'api-docs-and-import-flow' ? 1 : 0);
    console.log(`E2E test passed. ${verifiedScenarios} scenarios verified.`);
  } finally {
    if (browser) {
      await browser.close();
    }

    stopServer(server);
    await waitForExit(server);
    cleanupPaths.forEach(removePath);
  }
}

function createScenarios(tempDir) {
  const csvMappingFile = path.join(tempDir, 'mapped-sample.csv');
  fs.writeFileSync(
    csvMappingFile,
    [
      'name,age,email',
      'Alice,30,alice@example.com',
      'Bob,25,bob@example.com',
    ].join('\n'),
  );

  const semicolonCsvFile = path.join(tempDir, 'semicolon.csv');
  fs.writeFileSync(
    semicolonCsvFile,
    [
      'first_name;score;status',
      'Carla;98.5;active',
      'Dan;72.25;inactive',
    ].join('\n'),
  );

  const jsonFile = path.join(tempDir, 'typed-records.json');
  fs.writeFileSync(
    jsonFile,
    JSON.stringify([
      {
        full_name: 'Eve',
        age: 31,
        balance: 1234.56,
        active: true,
        note: null,
      },
      {
        full_name: 'Mallory',
        age: 29,
        balance: 0,
        active: false,
        note: 'review',
      },
    ], null, 2),
  );

  const xmlWithAttributesFile = path.join(tempDir, 'sample-with-attributes.xml');
  fs.writeFileSync(
    xmlWithAttributesFile,
    [
      '<?xml version="1.0" encoding="UTF-8"?>',
      '<records>',
      '  <record id="1" type="customer">',
      '    <name lang="en">Alice</name>',
      '    <age unit="years">30</age>',
      '  </record>',
      '  <record id="2" type="lead">',
      '    <name lang="de">Bob</name>',
      '    <age unit="years">25</age>',
      '  </record>',
      '</records>',
    ].join('\n'),
  );

  return [
    {
      name: 'csv-memory-default-flow',
      filePath: SAMPLE_FILE,
      fileType: 'csv',
      adapter: 'memory',
      expectedPreviewHeaders: ['name', 'age', 'email'],
      expectedPreviewRows: [
        ['Alice', '30', 'alice@example.com'],
        ['Bob', '25', 'bob@example.com'],
      ],
      expectedResult: {
        processed: 2,
        imported: 2,
        errors: 0,
      },
    },
    {
      name: 'csv-json-mapping-output',
      filePath: csvMappingFile,
      fileType: 'csv',
      adapter: 'json',
      mapping: {
        name: 'full_name',
        age: 'years',
        email: 'contact_email',
      },
      expectedPreviewHeaders: ['full_name', 'years', 'contact_email'],
      expectedPreviewRows: [
        ['Alice', '30', 'alice@example.com'],
        ['Bob', '25', 'bob@example.com'],
      ],
      expectedResult: {
        processed: 2,
        imported: 2,
        errors: 0,
      },
      expectedDownloadedJson: [
        { full_name: 'Alice', years: '30', contact_email: 'alice@example.com' },
        { full_name: 'Bob', years: '25', contact_email: 'bob@example.com' },
      ],
    },
    {
      name: 'csv-json-ignore-column-output',
      filePath: csvMappingFile,
      fileType: 'csv',
      adapter: 'json',
      mapping: {
        name: 'full_name',
        age: '',
        email: 'contact_email',
      },
      expectedPreviewHeaders: ['full_name', 'contact_email'],
      expectedPreviewRows: [
        ['Alice', 'alice@example.com'],
        ['Bob', 'bob@example.com'],
      ],
      expectedResult: {
        processed: 2,
        imported: 2,
        errors: 0,
      },
      expectedDownloadedJson: [
        { full_name: 'Alice', contact_email: 'alice@example.com' },
        { full_name: 'Bob', contact_email: 'bob@example.com' },
      ],
    },
    {
      name: 'csv-semicolon-autodetect-json-output',
      filePath: semicolonCsvFile,
      fileType: 'csv',
      adapter: 'json',
      expectedPreviewHeaders: ['first_name', 'score', 'status'],
      expectedPreviewRows: [
        ['Carla', '98.5', 'active'],
        ['Dan', '72.25', 'inactive'],
      ],
      expectedResult: {
        processed: 2,
        imported: 2,
        errors: 0,
      },
      expectedDownloadedJson: [
        { first_name: 'Carla', score: '98.5', status: 'active' },
        { first_name: 'Dan', score: '72.25', status: 'inactive' },
      ],
    },
    {
      name: 'json-typed-values-preserved',
      filePath: jsonFile,
      fileType: 'json',
      adapter: 'json',
      mapping: {
        full_name: 'name',
        balance: 'account_balance',
      },
      expectedPreviewHeaders: ['name', 'age', 'account_balance', 'active', 'note'],
      expectedPreviewRows: [
        ['Eve', '31', '1234.56', '1', ''],
        ['Mallory', '29', '0', '', 'review'],
      ],
      expectedResult: {
        processed: 2,
        imported: 2,
        errors: 0,
      },
      expectedDownloadedJson: [
        {
          name: 'Eve',
          age: 31,
          account_balance: 1234.56,
          active: true,
          note: null,
        },
        {
          name: 'Mallory',
          age: 29,
          account_balance: 0,
          active: false,
          note: 'review',
        },
      ],
    },
    {
      name: 'xml-xml-tag-mapping-output',
      filePath: SAMPLE_XML_FILE,
      fileType: 'xml',
      adapter: 'xml',
      mapping: {
        name: 'full_name',
        age: 'years',
        email: 'contact_email',
      },
      expectedPreviewHeaders: ['full_name', 'years', 'contact_email'],
      expectedPreviewRows: [
        ['Alice', '30', 'alice@example.com'],
        ['Bob', '25', 'bob@example.com'],
      ],
      expectedResult: {
        processed: 2,
        imported: 2,
        errors: 0,
      },
      expectedDownloadedXml:
        '<?xml version="1.0" encoding="UTF-8"?>\n'
        + '<rows>\n'
        + '  <row>\n'
        + '    <full_name>Alice</full_name>\n'
        + '    <years>30</years>\n'
        + '    <contact_email>alice@example.com</contact_email>\n'
        + '  </row>\n'
        + '  <row>\n'
        + '    <full_name>Bob</full_name>\n'
        + '    <years>25</years>\n'
        + '    <contact_email>bob@example.com</contact_email>\n'
        + '  </row>\n'
        + '</rows>\n',
    },
    {
      name: 'xml-json-attribute-mapping-output',
      filePath: xmlWithAttributesFile,
      fileType: 'xml',
      adapter: 'json',
      mapping: {
        '@id': 'record_id',
        '@type': 'record_type',
        'name': 'full_name',
        'name.@lang': 'locale',
        'age': 'years',
        'age.@unit': 'age_unit',
      },
      expectedPreviewHeaders: ['record_id', 'record_type', 'full_name', 'locale', 'years', 'age_unit'],
      expectedPreviewRows: [
        ['1', 'customer', 'Alice', 'en', '30', 'years'],
        ['2', 'lead', 'Bob', 'de', '25', 'years'],
      ],
      expectedResult: {
        processed: 2,
        imported: 2,
        errors: 0,
      },
      expectedDownloadedJson: [
        {
          record_id: '1',
          record_type: 'customer',
          full_name: 'Alice',
          locale: 'en',
          years: '30',
          age_unit: 'years',
        },
        {
          record_id: '2',
          record_type: 'lead',
          full_name: 'Bob',
          locale: 'de',
          years: '25',
          age_unit: 'years',
        },
      ],
    },
    {
      name: 'csv-corrupted-shows-schema-error-in-symfony-demo',
      filePath: CORRUPTED_CSV_FILE,
      fileType: 'csv',
      adapter: 'symfony',
      expectedSchemaError: 'Die CSV-Datei ist in Zeile 18 beschädigt: erwartet wurden 5 Spalten, gefunden wurden 6.',
    },
    {
      name: 'json-corrupted-shows-schema-error-in-symfony-demo',
      filePath: CORRUPTED_JSON_FILE,
      fileType: 'json',
      adapter: 'symfony',
      expectedSchemaError: 'Die JSON-Datei ist ungültig. Bitte prüfe Syntax, Kommata und Anführungszeichen.',
    },
  ];
}

async function runScenario(page, scenario, baseUrl) {
  const wizard = page.locator('turbo-frame#import_step');

  await page.goto(baseUrl, { waitUntil: 'domcontentloaded' });
  await expectStep(wizard, 'Quelldatei und Zielmodus wählen');

  await wizard.locator('#file').setInputFiles(scenario.filePath);
  await wizard.locator('#file_type').selectOption(scenario.fileType);
  await wizard.locator('#adapter').selectOption(scenario.adapter);
  await submitAndWaitForStep(
    page,
    wizard,
    wizard.getByRole('button', { name: 'Weiter zur Vorschau' }),
    'Zielschema und Struktur prüfen',
  );

  if (scenario.expectedSchemaError) {
    await expectSchemaErrorState(wizard, scenario.expectedSchemaError);
    return;
  }

  if (scenario.adapter === 'symfony') {
    await expectSchemaColumnBoard(wizard);
  }

  await submitAndWaitForStep(
    page,
    wizard,
    wizard.getByRole('button', { name: 'Weiter zum Mapping' }),
    'Felder zuordnen und Ziel prüfen',
  );

  await expectMappingBoard(wizard);

  if (scenario.mapping) {
    await applyMapping(wizard, scenario.mapping);
    await submitMappingPreview(page, wizard);
    await expectStep(wizard, 'Felder zuordnen und Ziel prüfen');
    await expectMappingBoard(wizard);
    await wizard.getByRole('heading', { name: 'Resultierende Vorschau', exact: true }).waitFor({ state: 'visible' });
    await waitForPreviewHeaders(wizard, scenario.expectedPreviewHeaders);
  }

  await wizard.getByRole('heading', { name: 'Resultierende Vorschau', exact: true }).waitFor({ state: 'visible' });
  await expectPreviewTable(wizard, scenario.expectedPreviewHeaders, scenario.expectedPreviewRows);

  const resultStep = submitAndWaitForStep(
    page,
    wizard,
    wizard.getByRole('button', { name: 'Import ausführen' }),
    'Laufstatus und Ergebnis',
  );
  await resultStep;

  await expectResultCards(wizard, scenario.expectedResult);

  if (scenario.expectedDownloadedJson) {
    const downloadPromise = page.waitForEvent('download');
    await wizard.getByRole('link', { name: 'JSON herunterladen' }).click();
    const download = await downloadPromise;
    const downloadPath = await download.path();
    assert.ok(downloadPath, `Download path missing for scenario "${scenario.name}".`);

    const downloadedJson = JSON.parse(fs.readFileSync(downloadPath, 'utf8'));
    assert.deepStrictEqual(downloadedJson, scenario.expectedDownloadedJson, `Downloaded JSON mismatch for scenario "${scenario.name}".`);
  }

  if (scenario.expectedDownloadedXml) {
    const downloadPromise = page.waitForEvent('download');
    await wizard.getByRole('link', { name: 'XML herunterladen' }).click();
    const download = await downloadPromise;
    const downloadPath = await download.path();
    assert.ok(downloadPath, `Download path missing for scenario "${scenario.name}".`);

    const downloadedXml = fs.readFileSync(downloadPath, 'utf8');
    assert.strictEqual(downloadedXml, scenario.expectedDownloadedXml, `Downloaded XML mismatch for scenario "${scenario.name}".`);
  }
}

async function runApiScenario(baseUrl, phpTempDir, databaseUrl) {
  const docsPage = await fetch(`${baseUrl}/api/docs`);
  assert.strictEqual(docsPage.status, 200, 'Swagger UI should be reachable.');

  const docsHtml = await docsPage.text();
  assert.ok(docsHtml.includes('/api/docs.json'), 'Swagger UI should reference the OpenAPI document.');

  const docsResponse = await fetch(`${baseUrl}/api/docs.json`);
  assert.strictEqual(docsResponse.status, 200, 'OpenAPI JSON should be reachable.');

  const openApi = await docsResponse.json();
  assert.strictEqual(openApi.info.title, 'Dynamic Data Importer Demo API');
  assert.ok(openApi.info.description.includes('Supported file types are CSV, XLS, XLSX, JSON, and XML.'));
  assert.ok(
    openApi.info.description.includes(
      'Available adapters are symfony for Doctrine-based persistence via the demo app and pdo for direct PDO-based inserts.',
    ),
  );
  assert.ok(openApi.info.description.includes('Use memory for an in-memory dry run without persisted artifacts.'));
  assert.ok(openApi.info.description.includes('Use json for JSON export output, xml for XML export output, and sql for SQL script export output.'));
  assert.ok(openApi.info.description.includes('The delimiter option is only relevant for CSV imports'));
  assert.ok(openApi.paths['/api/imports'], 'OpenAPI document should expose POST /api/imports.');
  assert.ok(openApi.paths['/api/imports/{jobId}'], 'OpenAPI document should expose GET /api/imports/{jobId}.');

  const form = new FormData();
  form.set('file', new Blob([fs.readFileSync(SAMPLE_FILE)], { type: 'text/csv' }), path.basename(SAMPLE_FILE));
  form.set('file_type', 'csv');
  form.set('adapter', 'json');
  form.set('table_name', 'api_import_rows');
  form.set('delimiter', ',');
  form.set('mapping', JSON.stringify({
    name: 'full_name',
    age: 'years',
    email: 'contact_email',
  }));

  const importResponse = await fetch(`${baseUrl}/api/imports`, {
    method: 'POST',
    body: form,
  });

  assert.strictEqual(importResponse.status, 202, 'API import should be queued.');

  const importPayload = await importResponse.json();
  assert.strictEqual(importPayload.status, 'queued');
  assert.ok(typeof importPayload.job_id === 'string' && importPayload.job_id.length > 0, 'Queued import should return a job id.');
  assert.strictEqual(importPayload.status_url, `/api/imports/${importPayload.job_id}`);

  await waitForQueueMessage(phpTempDir);
  await consumeAsyncImportMessage(phpTempDir, databaseUrl);

  const job = await pollJobStatus(`${baseUrl}${importPayload.status_url}`);
  assert.strictEqual(job.status, 'completed', 'Queued API import should complete.');
  assert.strictEqual(job.adapter, 'json');
  assert.deepStrictEqual(job.mapping, {
    name: 'full_name',
    age: 'years',
    email: 'contact_email',
  });
  assert.strictEqual(job.delimiter, ',');
  assert.ok(job.has_artifact, 'JSON adapter should produce an artifact.');
  assert.strictEqual(job.result.processed, 2);
  assert.strictEqual(job.result.imported, 2);
  assert.deepStrictEqual(job.result.errors, []);
}

function assertFileExists(filePath, message) {
  if (!fs.existsSync(filePath)) {
    throw new Error(`${message} Path: ${filePath}`);
  }
}

async function expectStep(wizard, heading) {
  await wizard.getByRole('heading', { name: heading, exact: true }).waitFor({ state: 'visible' });
}

async function submitAndWaitForStep(page, wizard, submitButton, expectedHeading) {
  const stepReady = wizard.getByRole('heading', { name: expectedHeading, exact: true }).waitFor({ state: 'visible' });
  const turboReady = waitForTurboFrameUpdate(page);
  await submitButton.click();
  await turboReady;
  await stepReady;
}

async function submitMappingPreview(page, wizard) {
  const responseReady = page.waitForResponse((response) => (
    response.request().method() === 'POST' && response.url().includes('/import/mapping')
  ));
  const turboReady = waitForTurboFrameUpdate(page);

  await wizard.getByRole('button', { name: 'Vorschau aktualisieren' }).click();

  await responseReady;
  await turboReady;
}

async function applyMapping(wizard, mapping) {
  for (const [sourceHeader, targetHeader] of Object.entries(mapping)) {
    const control = wizard.locator(`[name="mapping[${escapeAttributeValue(sourceHeader)}]"]`);
    const tagName = await control.evaluate((element) => element.tagName.toLowerCase());

    if (tagName === 'select') {
      await control.selectOption(targetHeader);
      continue;
    }

    await control.fill(targetHeader);
  }
}

async function expectMappingBoard(wizard) {
  const board = wizard.locator('.mapping-board').first();
  await board.waitFor({ state: 'visible' });

  const rows = board.locator('.mapping-row');
  const rowCount = await rows.count();
  assert.ok(rowCount > 0, 'Expected at least one mapping row.');
  await rows.first().locator('.mapping-box.is-source').waitFor({ state: 'visible' });
  await rows.first().locator('.mapping-box.is-target.mapping-target-control').waitFor({ state: 'visible' });
  await rows.first().locator('.mapping-arrow').waitFor({ state: 'visible' });

  const sourceValues = (await board.locator('.mapping-box.is-source .mapping-box-value').allTextContents())
    .map(normalizeCellText)
    .filter((value) => value.length > 0);
  assert.ok(sourceValues.length > 0, 'Expected at least one source field label in mapping board.');
  assert.strictEqual(sourceValues.length, rowCount, 'Each mapping row should expose one source label.');

  const targetControls = board.locator('.mapping-box.is-target select, .mapping-box.is-target input');
  assert.strictEqual(await targetControls.count(), rowCount, 'Each mapping row should expose one target control.');
}

async function expectSchemaColumnBoard(wizard) {
  const board = wizard.locator('.schema-column-board').first();
  await board.waitFor({ state: 'visible' });

  const rows = board.locator('.schema-column-row');
  const rowCount = await rows.count();
  assert.ok(rowCount > 0, 'Expected at least one schema column row.');
  await rows.first().locator('.mapping-box.is-source').waitFor({ state: 'visible' });
  await rows.first().locator('.mapping-box.is-target.mapping-target-control').waitFor({ state: 'visible' });
  await rows.first().locator('.schema-column-toggle').waitFor({ state: 'visible' });

  const sourceValues = (await board.locator('.mapping-box.is-source .mapping-box-value').allTextContents())
    .map(normalizeCellText)
    .filter((value) => value.length > 0);
  assert.ok(sourceValues.length > 0, 'Expected at least one source field label in schema board.');
  assert.strictEqual(sourceValues.length, rowCount, 'Each schema row should expose one source label.');

  const targetControls = board.locator('.mapping-box.is-target input[type="text"]');
  assert.strictEqual(await targetControls.count(), rowCount, 'Each schema row should expose one target column input.');

  const activeToggles = board.locator('.schema-column-toggle input[type="checkbox"]');
  assert.strictEqual(await activeToggles.count(), rowCount, 'Each schema row should expose one active toggle.');
}

async function expectPreviewTable(wizard, expectedHeaders, expectedRows) {
  const previewTable = wizard.locator('table').last();
  const headers = await previewTable.locator('thead th').allTextContents();
  assert.deepStrictEqual(headers.map(normalizeCellText), expectedHeaders, 'Preview headers mismatch.');

  const rows = await previewTable.locator('tbody tr').evaluateAll((elements) => (
    elements.map((row) => Array.from(row.querySelectorAll('td'), (cell) => cell.textContent || ''))
  ));

  const normalizedRows = rows.map((row) => row.map(normalizeCellText));
  assert.deepStrictEqual(normalizedRows, expectedRows, 'Preview rows mismatch.');
}

async function expectSchemaErrorState(wizard, expectedMessage) {
  const alert = wizard.locator('.alert.alert-danger').last();
  await alert.waitFor({ state: 'visible' });

  const message = normalizeCellText(await alert.textContent());
  assert.ok(
    message.includes(expectedMessage),
    `Expected schema error message "${expectedMessage}", got "${message}".`,
  );

  await assertPreviewTableMissing(wizard);
}

async function assertPreviewTableMissing(wizard) {
  const headings = await wizard.getByRole('heading', { name: 'Vorschau der Quelldaten', exact: true }).count();
  assert.strictEqual(headings, 0, 'Schema preview should not be rendered for corrupted input.');
}

async function waitForPreviewHeaders(wizard, expectedHeaders, timeout = 10000) {
  const previewTable = wizard.locator('table').last();
  await previewTable.waitFor({ state: 'visible', timeout });
  const deadline = Date.now() + timeout;

  while (Date.now() < deadline) {
    const headers = await previewTable.locator('thead th').allTextContents();
    if (JSON.stringify(headers.map(normalizeCellText)) === JSON.stringify(expectedHeaders)) {
      return;
    }

    await previewTable.page().waitForTimeout(100);
  }

  throw new Error(`Timed out waiting for preview headers: ${expectedHeaders.join(', ')}`);
}

async function expectResultCards(wizard, expectedResult) {
  await expectStatCardValue(wizard, 'Verarbeitet', String(expectedResult.processed));
  await expectStatCardValue(wizard, 'Importiert', String(expectedResult.imported));
  await expectStatCardValue(wizard, 'Fehler', String(expectedResult.errors));
}

async function expectStatCardValue(wizard, label, expectedValue) {
  const title = wizard.locator('.card-title', { hasText: label }).first();
  await title.waitFor({ state: 'visible' });
  const value = normalizeCellText(await title.locator('xpath=following-sibling::*[contains(@class, "display-4")][1]').textContent());
  assert.strictEqual(value, expectedValue, `${label} value mismatch.`);
}

function waitForTurboFrameUpdate(page, frameId = 'import_step', timeout = 10000) {
  return page.evaluate(
    ({ frameId, timeout }) => new Promise((resolve, reject) => {
      const frame = document.getElementById(frameId);
      if (!frame) {
        reject(new Error(`Turbo frame "${frameId}" not found.`));
        return;
      }

      let settled = false;
      let observer;
      const finish = () => {
        if (settled) {
          return;
        }

        settled = true;
        clearTimeout(timer);
        if (observer) {
          observer.disconnect();
        }
        frame.removeEventListener('turbo:frame-load', onLoad);
        resolve();
      };
      const onLoad = () => {
        finish();
      };
      const timer = setTimeout(() => {
        if (settled) {
          return;
        }

        settled = true;
        if (observer) {
          observer.disconnect();
        }
        frame.removeEventListener('turbo:frame-load', onLoad);
        reject(new Error(`Timed out waiting for Turbo frame "${frameId}" to update.`));
      }, timeout);

      frame.addEventListener('turbo:frame-load', onLoad, { once: true });
      observer = new MutationObserver(() => finish());
      observer.observe(frame, {
        childList: true,
        subtree: true,
      });
    }),
    { frameId, timeout },
  );
}

function escapeAttributeValue(value) {
  return value.replace(/\\/g, '\\\\').replace(/"/g, '\\"');
}

function normalizeCellText(value) {
  return String(value ?? '').replace(/\s+/g, ' ').trim();
}

async function waitForQueueMessage(phpTempDir, timeout = 5000) {
  const queueDir = path.join(phpTempDir, 'dynamic-data-importer', 'messenger', 'async_imports');
  const deadline = Date.now() + timeout;

  while (Date.now() < deadline) {
    const queueFiles = fs.existsSync(queueDir)
      ? fs.readdirSync(queueDir).filter((file) => file.endsWith('.json'))
      : [];

    if (queueFiles.length > 0) {
      return;
    }

    await new Promise((resolve) => {
      setTimeout(resolve, 100);
    });
  }

  throw new Error(`Timed out waiting for async queue message in ${queueDir}.`);
}

async function consumeAsyncImportMessage(phpTempDir, databaseUrl) {
  const consumer = spawn(
    'php',
    [CONSOLE, 'messenger:consume', 'async_imports', '--limit=1', '--time-limit=30', '--env=test', '--no-debug', '--no-reset', '--no-interaction'],
    {
      cwd: PROJECT_ROOT,
      env: {
        ...process.env,
        DATABASE_URL: databaseUrl,
        APP_ENV: 'test',
        APP_DEBUG: '0',
        TMPDIR: phpTempDir,
        TMP: phpTempDir,
        TEMP: phpTempDir,
      },
      stdio: ['ignore', 'pipe', 'pipe'],
    },
  );

  let stderr = '';
  let stdout = '';

  consumer.stdout.on('data', (chunk) => {
    stdout += String(chunk);
  });
  consumer.stderr.on('data', (chunk) => {
    stderr += String(chunk);
  });

  await new Promise((resolve, reject) => {
    consumer.once('exit', (code) => {
      if (code === 0) {
        resolve();
        return;
      }

      reject(new Error(`Messenger consumer failed with code ${code}. stdout: ${stdout.trim()} stderr: ${stderr.trim()}`));
    });
    consumer.once('error', reject);
  });
}

async function pollJobStatus(statusUrl, timeout = 15000) {
  const deadline = Date.now() + timeout;
  let lastPayload = null;

  while (Date.now() < deadline) {
    const response = await fetch(statusUrl);
    assert.strictEqual(response.status, 200, 'Job status endpoint should remain reachable.');

    const payload = await response.json();
    lastPayload = payload;

    if (payload.status === 'completed' || payload.status === 'failed') {
      return payload;
    }

    await new Promise((resolve) => {
      setTimeout(resolve, 150);
    });
  }

  throw new Error(`Timed out waiting for job completion. Last payload: ${JSON.stringify(lastPayload)}`);
}

async function startPhpServer(port, phpTempDir, databaseUrl) {
  console.log('Starting PHP server...');
  let lastStdErr = '';
  const server = spawn(
    'php',
    ['-S', `${HOST}:${port}`, '-t', PUBLIC_DIR],
    {
      cwd: PROJECT_ROOT,
      env: {
        ...process.env,
        DATABASE_URL: databaseUrl,
        APP_ENV: 'test',
        APP_DEBUG: '0',
        TMPDIR: phpTempDir,
        TMP: phpTempDir,
        TEMP: phpTempDir,
      },
      stdio: ['ignore', 'pipe', 'pipe'],
    },
  );

  server.stdout.on('data', () => {});
  server.stderr.on('data', (chunk) => {
    lastStdErr = String(chunk).trim() || lastStdErr;
  });

  await waitForServer(HOST, port, server, () => lastStdErr);

  return server;
}

function waitForServer(host, port, server, getLastStdErr = () => '') {
  return new Promise((resolve, reject) => {
    const deadline = Date.now() + 10000;

    const tryConnect = () => {
      if (server.exitCode !== null) {
        const stderr = getLastStdErr();
        const suffix = stderr ? ` Last stderr: ${stderr}` : '';
        reject(new Error(`PHP server exited early with code ${server.exitCode}.${suffix}`));
        return;
      }

      const socket = net.connect({ host, port });
      socket.once('connect', () => {
        socket.end();
        resolve();
      });
      socket.once('error', () => {
        socket.destroy();
        if (Date.now() > deadline) {
          reject(new Error('Timed out waiting for PHP server.'));
          return;
        }
        setTimeout(tryConnect, 100);
      });
    };

    tryConnect();
  });
}

function getAvailablePort() {
  return new Promise((resolve, reject) => {
    const server = net.createServer();

    server.listen(0, HOST, () => {
      const address = server.address();
      if (!address || typeof address === 'string') {
        server.close(() => reject(new Error('Could not determine an open port.')));
        return;
      }

      const { port } = address;
      server.close((error) => {
        if (error) {
          reject(error);
          return;
        }

        resolve(port);
      });
    });

    server.on('error', reject);
  });
}

function waitForExit(child) {
  return new Promise((resolve) => {
    if (child.exitCode !== null) {
      resolve();
      return;
    }

    const forceKillTimer = setTimeout(() => {
      try {
        child.kill('SIGKILL');
      } catch (_) {
        resolve();
      }
    }, 2000);

    child.once('exit', () => {
      clearTimeout(forceKillTimer);
      resolve();
    });
  });
}

function stopServer(child) {
  if (child.exitCode !== null) {
    return;
  }

  try {
    child.kill('SIGTERM');
  } catch (_) {
    // Nothing else to do here; waitForExit will resolve or escalate to SIGKILL.
  }
}

function removePath(targetPath) {
  if (!fs.existsSync(targetPath)) {
    return;
  }

  fs.rmSync(targetPath, { recursive: true, force: true });
}

main().catch((error) => {
  console.error(error);
  process.exitCode = 1;
});
