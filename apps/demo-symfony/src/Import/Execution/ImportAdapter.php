<?php

declare(strict_types=1);

namespace App\Import\Execution;

enum ImportAdapter: string
{
    case Memory = 'memory';
    case Json = 'json';
    case Xml = 'xml';
    case Sql = 'sql';
    case Symfony = 'symfony';
}
