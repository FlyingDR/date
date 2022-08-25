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
    private static ?\DateInterval $adjustment = null;
    private static ?\DateTimeZone $timezone = null;

    /**
     * Get current DateTime
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
        if (self::isAdjustmentAllowed()) {
            $adjustment = self::getAdjustment();
            if ($adjustment) {
                /**
                 * Apply date adjustment, but remove microseconds.
                 *
                 * Reasons for it are explained in the comment of the "adjust" method.
                 *
                 * @see adjust()
                 */
                $ms = new \DateInterval('PT0S');
                $ms->f = (float)('0.' . $now->format('u'));
                $now = $now->add($adjustment)->sub($ms);
            }
        }
        return $now;
    }

    /**
     * Get timezone to use for creating dates
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
     * Adjust current date by given time shift
     *
     * IMPORTANT: Date adjustments should only be used in tests, not in real code!
     */
    public static function adjust(\DateInterval|\DateTimeInterface|null $now = null): void
    {
        if ($now instanceof \DateTimeInterface) {
            self::$adjustment = (new \DateTimeImmutable())->diff($now);
        } else {
            self::$adjustment = $now;
        }
        if (self::$adjustment) {
            /**
             * Strip microseconds part of the date adjustment interval.
             *
             * It should be safe because testing time shifts with microseconds precision on intervals
             * less than a second is more reliable with use of the usleep() and for larger intervals
             * microseconds include may introduce difference of the whole second which may cause tests
             * to break from time to time.
             */
            self::$adjustment->f = 0;
        }
    }

    /**
     * Get currently defined date adjustment
     */
    public static function getAdjustment(): ?\DateInterval
    {
        return self::$adjustment;
    }

    /**
     * Update date adjustment allowing status
     *
     * IMPORTANT: Date adjustments should only be used in tests, not in real code!
     */
    public static function allowAdjustment(bool $status): void
    {
        self::$adjustmentAllowed = $status;
    }

    /**
     * Check if date adjustment is allowed
     */
    public static function isAdjustmentAllowed(): bool
    {
        return self::$adjustmentAllowed;
    }
}
