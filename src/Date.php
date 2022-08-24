<?php

declare(strict_types=1);

namespace Flying\Date;

/**
 * DateTime generator with support for adjusting current time
 * to allow proper testing of time-sensitive scenarios
 */
final class Date
{
    private static bool $adjustmentAllowed = false;
    private static ?\DateInterval $shift = null;
    private static ?\DateTimeZone $timezone = null;

    /**
     * Get current time
     */
    public static function now(): \DateTimeImmutable
    {
        return self::from('now');
    }

    /**
     * Create DateTime object from given information
     */
    public static function from(\DateTimeInterface|\DateInterval|string $date, ?\DateTimeZone $timezone = null): \DateTimeImmutable
    {
        /** @noinspection PhpUnhandledExceptionInspection */
        $now = match (true) {
            $date instanceof \DateTimeInterface => \DateTimeImmutable::createFromInterface($date),
            $date instanceof \DateInterval => (new \DateTimeImmutable())->add($date),
            is_string($date) => new \DateTimeImmutable($date),
        };
        $now = $now->setTimezone($timezone ?? self::getTimezone());
        if (self::$shift !== null) {
            // It is necessary to remove microseconds
            $ms = new \DateInterval('PT0S');
            $ms->f = (float)('0.' . $now->format('u'));
            $now = $now->add(self::$shift)->sub($ms);
        }
        return $now;
    }

    /**
     * Get current timezone that is used for creating dates
     */
    public static function getTimezone(): \DateTimeZone
    {
        return self::$timezone ??= new \DateTimeZone(date_default_timezone_get());
    }

    /**
     * Set default timezone
     */
    public static function setTimezone(?\DateTimeZone $timezone = null): void
    {
        self::$timezone = $timezone;
    }

    /**
     * Adjust current time to given new "now" time or to given time shift
     *
     * IMPORTANT: Date adjustments should only be used in tests, not in real code!
     */
    public static function adjust(\DateInterval|\DateTimeInterface|null $now = null): void
    {
        if (!self::$adjustmentAllowed) {
            throw new \RuntimeException('Date adjustments is not allowed');
        }
        if ($now instanceof \DateTimeInterface) {
            self::$shift = (new \DateTimeImmutable())->diff($now);
            self::$shift->f = 0;
        } else {
            self::$shift = $now;
        }
    }

    /**
     * Update time adjustment allowing status.
     * Returns previous status.
     *
     * IMPORTANT: Date adjustments should only be used in tests, not in real code!
     */
    public static function allowAdjustment(bool $status): bool
    {
        $current = self::$adjustmentAllowed;
        self::$adjustmentAllowed = $status;
        return $current;
    }
}
