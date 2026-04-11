<?php

declare(strict_types=1);

namespace DynamicDataImporter\Domain\Transformer\Callback;

use DynamicDataImporter\Domain\Model\Row;
use DynamicDataImporter\Domain\Transformer\TransformerInterface;

final readonly class CallbackTransformer implements TransformerInterface
{
    private \Closure $callback;

    /** @param callable(Row): Row $callback */
    public function __construct(callable $callback)
    {
        $this->callback = \Closure::fromCallable($callback);
    }

    public function transform(Row $row): Row
    {
        return ($this->callback)($row);
    }

    /**
     * @param list<string> $headers
     *
     * @return list<string>
     */
    public function transformHeaders(array $headers): array
    {
        return $headers;
    }
}
