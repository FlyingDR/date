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
    public static function from(\DateTimeInterface|\DateInterval|string $date, \DateTimeZone|string|null $timezone = null): \DateTimeImmutable
    {
        $timezone = match (true) {
            $timezone instanceof \DateTimeZone => $timezone,
            is_string($timezone) => new \DateTimeZone($timezone),
            default => self::getTimezone(),
        };
        /** @noinspection PhpUnhandledExceptionInspection */
        $now = match (true) {
            $date instanceof \DateTimeInterface => new \DateTimeImmutable($date->setTimezone($timezone)->format('Y-m-d\TH:i:s'), $timezone),
            $date instanceof \DateInterval => (new \DateTimeImmutable('now', $timezone))->add($date),
            is_string($date) => new \DateTimeImmutable($date, $timezone),
        };
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
                $now = $now->add($adjustment);
                $ms = new \DateInterval('PT0S');
                /**
                 * There is a relatively small, but still possible chance that after the initial
                 * subtraction of the microseconds' part resulted object will have 1 microsecond
                 * instead of zero.
                 *
                 * It may result in incorrect dates comparison
                 */
                do {
                    $ms->f = (float)('0.' . $now->format('u'));
                    $now = $now->sub($ms);
                } while ($now->format('u') !== '000000');
            }
        }
        return $now;
    }

    /**
     * Create DateTime object from given formatted string
     */
    public static function fromFormat(string $format, string $datetime, \DateTimeZone|string|null $timezone = null): \DateTimeImmutable|bool
    {
        $date = \DateTimeImmutable::createFromFormat($format, $datetime, $timezone);
        return $date instanceof \DateTimeImmutable ? self::from($date, $timezone) : $date;
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
    public static function setTimezone(\DateTimeZone|string|null $timezone = null): void
    {
        if (is_string($timezone)) {
            $timezone = new \DateTimeZone($timezone);
        }
        self::$timezone = $timezone;
    }

    /**
     * Adjust the current date by given time shift
     *
     * IMPORTANT: Date adjustments should only be used in tests, not in real code!
     *
     * @throws \Exception
     */
    public static function adjust(\DateInterval|\DateTimeInterface|string|null $adjustment = null): void
    {
        if (is_string($adjustment)) {
            $exception = null;
            if ($adjustment[0] === 'P') {
                try {
                    $adjustment = new \DateInterval($adjustment);
                } catch (\Exception $e) {
                    $exception = $e;
                }
            }
            if (is_string($adjustment)) {
                try {
                    $adjustment = new \DateTimeImmutable($adjustment);
                } catch (\Exception $e) {
                    $exception = $e;
                }
            }
            if (is_string($adjustment)) {
                $exception ??= new \Exception('Failed to recognize given time shift definition: ' . $adjustment);
                throw $exception;
            }
        }
        if ($adjustment instanceof \DateTimeInterface) {
            $base = new \DateTimeImmutable();
            $diff = $base->diff($adjustment);
            /**
             * It is possible that we have a time difference that is slightly less that a second.
             * Since we're stripping microseconds, it may result in losing a whole second of time
             */
            if (round($diff->f) === 1.0) {
                $base = $base->sub(new \DateInterval('PT1S'));
            }
            self::$adjustment = $base->diff($adjustment);
        } else {
            self::$adjustment = $adjustment;
        }
        if (self::$adjustment) {
            /**
             * Strip microseconds part of the date adjustment interval.
             *
             * It should be safe because testing time shifts with microsecond precision on intervals
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
