<?php

declare(strict_types=1);

namespace DynamicDataImporter\Tests\Support\Fuzz;

final class FuzzSeedSequence
{
    public static function provide(int $defaultCount = 20): iterable
    {
        $startSeed = self::envInt('DDI_FUZZ_SEED_START', 1);
        $seedCount = self::envInt('DDI_FUZZ_SEED_COUNT', $defaultCount);

        for ($seed = $startSeed; $seed < $startSeed + $seedCount; ++$seed) {
            yield 'seed-' . $seed => [$seed];
        }
    }

    private static function envInt(string $name, int $default): int
    {
        $value = getenv($name);
        if ($value === false || $value === '') {
            return $default;
        }

        $parsed = filter_var($value, \FILTER_VALIDATE_INT);

        return is_int($parsed) ? $parsed : $default;
    }
}
