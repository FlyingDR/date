<?php /** @noinspection DevelopmentDependenciesUsageInspection */

declare(strict_types=1);

namespace Flying\Date\PHPUnit\Listener;

use Flying\Date\Date;
use Flying\Date\PHPUnit\Attribute\AdjustableDate;
use PHPUnit\Framework\Test;
use PHPUnit\Framework\TestListener;
use PHPUnit\Framework\TestListenerDefaultImplementation;

class AdjustableDateListener implements TestListener
{
    use TestListenerDefaultImplementation;

    /** @var AdjustableDate[] */
    private array $cache = [];

    public function startTest(Test $test): void
    {
        if (!array_key_exists($test::class, $this->cache)) {
            $attributes = (new \ReflectionObject($test))->getAttributes(AdjustableDate::class);
            $attribute = array_shift($attributes);
            $this->cache[$test::class] = $attribute?->newInstance();
        }
        $attribute = $this->cache[$test::class];
        Date::allowAdjustment(($attribute?->isEnabled()) ?? false);
        Date::setTimezone(($attribute?->getTimezone()) ?? null);
        Date::adjust();
    }

    public function endTest(Test $test, float $time): void
    {
        Date::allowAdjustment(false);
        Date::setTimezone();
        Date::adjust();
    }
}
