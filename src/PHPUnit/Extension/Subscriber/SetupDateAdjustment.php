<?php /** @noinspection DevelopmentDependenciesUsageInspection */

declare(strict_types=1);

namespace Flying\Date\PHPUnit\Extension\Subscriber;

use Flying\Date\Date;
use Flying\Date\PHPUnit\Attribute\AdjustableDate;
use PHPUnit\Event\Code\TestMethod;
use PHPUnit\Event\Test\Prepared;
use PHPUnit\Event\Test\PreparedSubscriber;

class SetupDateAdjustment implements PreparedSubscriber
{
    /** @var AdjustableDate[] */
    private array $cache = [];

    /**
     * @throws \ReflectionException
     */
    public function notify(Prepared $event): void
    {
        $test = $event->test();
        if (!$test instanceof TestMethod) {
            return;
        }
        $reflection = null;
        $className = $test->className();
        if (!array_key_exists($className, $this->cache)) {
            $reflection ??= new \ReflectionClass($className);
            $ar = $reflection->getAttributes(AdjustableDate::class)[0] ?? null;
            $this->cache[$className] = $ar?->newInstance();
        }
        $method = $test->methodName();
        $methodKey = $className . '::' . $method;
        if (!array_key_exists($methodKey, $this->cache)) {
            $reflection ??= new \ReflectionClass($className);
            $ar = null;
            if ($reflection->hasMethod($method)) {
                $ar = $reflection->getMethod($method)->getAttributes(AdjustableDate::class)[0] ?? null;
            }
            $this->cache[$methodKey] = $ar?->newInstance();
        }
        Date::allowAdjustment($this->cache[$methodKey]?->isEnabled() ?? $this->cache[$className]?->isEnabled() ?? false);
        Date::setTimezone($this->cache[$methodKey]?->getTimezone() ?? $this->cache[$className]?->getTimezone() ?? null);
        Date::adjust();
    }
}
