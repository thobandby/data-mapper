<?php

declare(strict_types=1);

namespace DynamicDataImporter\Infrastructure\Persistence;

use DynamicDataImporter\Domain\Model\Row;
use DynamicDataImporter\Port\Persistence\PersisterInterface;

/**
 * @phpstan-import-type RowData from Row
 * @phpstan-import-type RowValue from Row
 */
final class XmlPersister implements PersisterInterface
{
    /** @var list<object> */
    private array $entities = [];
    private readonly OutputDirectoryInitializer $directoryInitializer;
    private readonly EntityDataExtractor $entityDataExtractor;
    private readonly XmlFieldSerializer $fieldSerializer;

    public function __construct(
        private readonly string $outputFile,
    ) {
        $this->directoryInitializer = new OutputDirectoryInitializer();
        $this->entityDataExtractor = new EntityDataExtractor();
        $this->fieldSerializer = new XmlFieldSerializer();
        $this->directoryInitializer->ensureFor($outputFile);
    }

    public function persist(object $entity): void
    {
        $this->entities[] = $entity;
    }

    public function flush(): void
    {
        if ($this->entities === []) {
            return;
        }

        $xml = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<rows>\n";

        foreach ($this->entities as $entity) {
            $xml .= "  <row>\n";

            foreach ($this->entityDataExtractor->extract($entity) as $key => $value) {
                $xml .= $this->fieldSerializer->serialize((string) $key, $value);
            }

            $xml .= "  </row>\n";
        }

        $xml .= "</rows>\n";

        file_put_contents($this->outputFile, $xml);
        $this->entities = [];
    }
}
