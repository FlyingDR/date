<?php

declare(strict_types=1);

namespace Flying\Date\PHPUnit\Attribute;

/**
 * Attribute to use for test classes to automatically configure
 * date adjustment for them
 */
#[\Attribute(\Attribute::TARGET_CLASS|\Attribute::TARGET_METHOD)]
final class AdjustableDate
{
    private bool $enabled;
    private ?\DateTimeZone $timezone;

    public function __construct(
        bool $enabled = true,
        \DateTimeZone|string|null $timezone = null,
    ) {
        $this->enabled = $enabled;
        if (is_string($timezone)) {
            if (!in_array($timezone, \DateTimeZone::listIdentifiers(), true)) {
                throw new \InvalidArgumentException('Invalid timezone: ' . $timezone);
            }
            $timezone = new \DateTimeZone($timezone);
        }
        $this->timezone = $timezone;
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function getTimezone(): ?\DateTimeZone
    {
        return $this->timezone;
    }
}
