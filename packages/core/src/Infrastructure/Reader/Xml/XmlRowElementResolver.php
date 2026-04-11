<?php

declare(strict_types=1);

namespace DynamicDataImporter\Infrastructure\Reader\Xml;

final class XmlRowElementResolver
{
    /**
     * @return list<\SimpleXMLElement>
     */
    public function resolve(\SimpleXMLElement $root): array
    {
        $children = iterator_to_array($root->children(), false);
        if ($children === []) {
            return [];
        }

        foreach ($children as $child) {
            if ($child->count() > 0) {
                return $children;
            }
        }

        return [$root];
    }
}
