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
        $reflection = null;
        $classKey = $test::class;
        if (!array_key_exists($classKey, $this->cache)) {
            $reflection ??= new \ReflectionObject($test);
            $ar = $reflection->getAttributes(AdjustableDate::class)[0] ?? null;
            $this->cache[$classKey] = $ar?->newInstance();
        }
        $methodKey = $test::class . '::' . $test->getName();
        if (!array_key_exists($methodKey, $this->cache)) {
            $reflection ??= new \ReflectionObject($test);
            $ar = null;
            $method = $test->getName();
            if ($reflection->hasMethod($method)) {
                $ar = $reflection->getMethod($method)->getAttributes(AdjustableDate::class)[0] ?? null;
            }
            $this->cache[$methodKey] = $ar?->newInstance();
        }
        Date::allowAdjustment($this->cache[$methodKey]?->isEnabled() ?? $this->cache[$classKey]?->isEnabled() ?? false);
        Date::setTimezone($this->cache[$methodKey]?->getTimezone() ?? $this->cache[$classKey]?->getTimezone() ?? null);
        Date::adjust();
    }

    public function endTest(Test $test, float $time): void
    {
        Date::allowAdjustment(false);
        Date::setTimezone();
        Date::adjust();
    }
}
