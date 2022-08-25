<?php

declare(strict_types=1);

namespace Flying\Date\PHPUnit\Helper;

/**
 * Trait for time-sensitive scenarios
 * to ensure that each test will start at the beginning of the second
 * so there will be less time to break tests which are based on dates comparison
 * with second precision due to crossing of the boundary of the second
 */
trait EnsureStartOfTheSecondTrait
{
    protected function ensureStartOfTheSecond(): void
    {
        do {
            $now = microtime(true);
            $micro = ($now - floor($now)) * 1_000_000;
            if ($micro < 10_000) {
                return;
            }
            usleep(1_000);
        } while ($micro > 10_000);
    }
}
