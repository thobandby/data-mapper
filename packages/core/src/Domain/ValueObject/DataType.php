<?php

declare(strict_types=1);

namespace DynamicDataImporter\Domain\ValueObject;

enum DataType: string
{
    case STRING = 'string';
    case INT = 'int';
    case FLOAT = 'float';
    case BOOL = 'bool';
    case DATE = 'date';
}
