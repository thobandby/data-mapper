<?php

declare(strict_types=1);

namespace DynamicDataImporter\Infrastructure\Reader\Xml;

use DynamicDataImporter\Domain\Exception\ImporterException;
use DynamicDataImporter\Infrastructure\Reader\FileContentLoader;

final class XmlDocumentLoader
{
    private readonly FileContentLoader $fileContentLoader;

    public function __construct()
    {
        $this->fileContentLoader = new FileContentLoader();
    }

    public function load(string $filePath): \SimpleXMLElement
    {
        $content = $this->fileContentLoader->load($filePath);
        if ($this->containsDocumentTypeDeclaration($content)) {
            throw ImporterException::invalidXml('DOCTYPE declarations are not allowed.');
        }

        $xml = simplexml_load_string($content, \SimpleXMLElement::class, \LIBXML_NONET | \LIBXML_NOCDATA);
        if ($xml instanceof \SimpleXMLElement) {
            return $xml;
        }

        $error = libxml_get_last_error();
        $message = $error !== false ? trim($error->message) : 'Unknown XML parsing error.';

        throw ImporterException::invalidXml($message);
    }

    private function containsDocumentTypeDeclaration(string $content): bool
    {
        return preg_match('/<!DOCTYPE/i', $content) === 1;
    }
}
