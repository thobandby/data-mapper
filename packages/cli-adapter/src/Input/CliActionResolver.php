<?php

declare(strict_types=1);

namespace DynamicDataImporter\Cli\Input;

use DynamicDataImporter\Cli\Exception\CliUsageException;

final class CliActionResolver
{
    private const ACTIONS = ['help', 'analyze', 'preview', 'execute', 'wizard'];

    /**
     * @param list<string> $args
     */
    public function resolve(array &$args): string
    {
        $action = $this->extractAction($args);

        if (! \in_array($action, self::ACTIONS, true)) {
            throw new CliUsageException(\sprintf('Unsupported action: %s', $action));
        }

        return $action;
    }

    /**
     * @param list<string> $args
     */
    private function extractAction(array &$args): string
    {
        if (! isset($args[0]) || str_starts_with($args[0], '--')) {
            return 'wizard';
        }

        return strtolower((string) \array_shift($args));
    }
}
