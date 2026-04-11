<?php

declare(strict_types=1);

namespace DynamicDataImporter\Infrastructure\Reader\Xml;

final readonly class XmlPathHelper
{
    public function __construct(
        private string $attributePrefix,
        private string $pathSeparator,
    ) {
    }

    public function elementPath(string $path, string $name): string
    {
        return $path === '' ? $name : $path . $this->pathSeparator . $name;
    }

    public function attributePath(string $path, string $name): string
    {
        return $this->elementPath($path, $this->attributePrefix . $name);
    }
}
