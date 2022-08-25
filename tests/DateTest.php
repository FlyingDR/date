<?php

declare(strict_types=1);

namespace Flying\Date\Tests;

use Flying\Date\Date;
use Flying\Date\PHPUnit\Attribute\AdjustableDate;
use Flying\Date\PHPUnit\Helper\EnsureStartOfTheSecondTrait;
use PHPUnit\Framework\TestCase;

class DateTest extends TestCase
{
    use EnsureStartOfTheSecondTrait;

    protected function setUp(): void
    {
        $this->ensureStartOfTheSecond();
    }

    public function testDefaultTimezoneIsUsedIfNotDefinedExplicitly(): void
    {
        $default = date_default_timezone_get();
        self::assertEquals($default, Date::getTimezone()->getName());

        $timezone = $this->getNonDefaultTimezone();
        Date::setTimezone($timezone);
        self::assertNotEquals($default, Date::getTimezone()->getName());
        self::assertEquals($timezone, Date::getTimezone());

        Date::setTimezone();
        self::assertEquals($default, Date::getTimezone()->getName());
    }

    public function testNowReturnsCurrentDate(): void
    {
        $now = Date::now();
        self::assertDateEquals(new \DateTimeImmutable(), $now);
        self::assertEquals($this->getDefaultTimezone(), $now->getTimezone());

        Date::setTimezone($this->getNonDefaultTimezone());
        $now = Date::now();
        self::assertDateEquals(new \DateTimeImmutable(), $now);
        self::assertEquals($this->getNonDefaultTimezone(), $now->getTimezone());
    }

    public function testCreatingDatesFromDifferentFormats(): void
    {
        $reference = $this->getReferenceDate();

        $date = Date::from($reference);
        self::assertNotSame($reference, $date);
        self::assertDateEquals($reference, $date);

        $interval = Date::now()->diff($reference);
        $date = Date::from($interval);
        self::assertNotSame($reference, $date);
        self::assertDateEquals($reference, $date);

        $date = Date::from($reference->format(\DateTimeInterface::ATOM));
        self::assertNotSame($reference, $date);
        self::assertDateEquals($reference, $date);

        $date = Date::from('2022-08-01');
        self::assertEquals('2022-08-01', $date->format('Y-m-d'));

        $date = Date::from('+3 weeks');
        $expected = (new \DateTimeImmutable())->add(new \DateInterval('P3W'))->format('Y-m-d');
        self::assertEquals($expected, $date->format('Y-m-d'));
    }

    /**
     * @throws \Exception
     */
    public function testTimezoneAppliesAtTheTimeOfDateCreation(): void
    {
        $date = '2022-08-01T12:23:34Z';
        $tz1 = $this->getDefaultTimezone();
        $tz2 = $this->getNonDefaultTimezone();

        Date::setTimezone($tz1);
        self::assertEquals(
            Date::from($date)->format(\DateTimeInterface::ATOM),
            (new \DateTimeImmutable($date, $tz1))->format(\DateTimeInterface::ATOM),
        );

        Date::setTimezone($tz2);
        self::assertEquals(
            Date::from($date)->format(\DateTimeInterface::ATOM),
            (new \DateTimeImmutable($date, $tz2))->format(\DateTimeInterface::ATOM),
        );
    }

    public function testDefaultTimezoneIsUsedUnlessPassedExplicitly(): void
    {
        $reference = $this->getReferenceDate();

        $date = Date::from($reference);
        self::assertEquals($this->getDefaultTimezone(), $date->getTimezone());

        Date::setTimezone($this->getNonDefaultTimezone());
        $date = Date::from($reference);
        self::assertEquals($this->getNonDefaultTimezone(), $date->getTimezone());

        Date::setTimezone($this->getDefaultTimezone()->getName());
        $date = Date::from($reference);
        self::assertEquals($this->getDefaultTimezone(), $date->getTimezone());

        Date::setTimezone();
        $date = Date::from($reference);
        self::assertEquals($this->getDefaultTimezone(), $date->getTimezone());

        $timezone = $this->getNonDefaultTimezone();
        $date = Date::from($reference, $timezone);
        self::assertEquals($timezone, $date->getTimezone());

        $timezone = $this->getNonDefaultTimezone();
        $date = Date::from($reference, $timezone->getName());
        self::assertEquals($timezone, $date->getTimezone());
    }

    #[AdjustableDate]
    public function testDateAdjustmentAppliesToDateGeneratorMethods(): void
    {
        $reference = $this->getReferenceDate();
        $diff = (new \DateTimeImmutable())->diff($reference);

        Date::adjust($reference);
        $now = Date::now();
        self::assertNotSame($reference, $now);
        self::assertDateEquals($reference, $now);
        self::assertDateNotEquals(new \DateTimeImmutable(), $now);
        self::assertDateEquals((new \DateTimeImmutable())->add($diff), $now);

        $interval = new \DateInterval('P1DT2H3M4S');
        Date::adjust($interval);
        self::assertEquals($interval, Date::getAdjustment());
        $now = Date::now();
        self::assertDateNotEquals(new \DateTimeImmutable(), $now);
        self::assertDateEquals((new \DateTimeImmutable())->add($interval), $now);

        Date::adjust();
        self::assertNull(Date::getAdjustment());
        $now = Date::now();
        self::assertDateEquals(new \DateTimeImmutable(), $now);
    }

    #[AdjustableDate]
    public function testDateAdjustmentsIsOnlyAllowedWhenExplicitlyEnabled(): void
    {
        $reference = $this->getReferenceDate();

        Date::allowAdjustment(true);
        Date::adjust($reference);
        $now = Date::now();
        self::assertDateEquals($reference, $now);

        self::assertTrue(Date::isAdjustmentAllowed());
        Date::allowAdjustment(false);
        self::assertFalse(Date::isAdjustmentAllowed());
        Date::adjust($reference);
        $now = Date::now();
        self::assertDateEquals(new \DateTimeImmutable(), $now);
    }

    private function getReferenceDate(): \DateTimeImmutable
    {
        return \DateTimeImmutable::createFromFormat(\DateTimeInterface::ATOM, '2022-08-01T12:23:34Z', $this->getDefaultTimezone());
    }

    private function getDefaultTimezone(): \DateTimeZone
    {
        return new \DateTimeZone(date_default_timezone_get());
    }

    private function getNonDefaultTimezone(): \DateTimeZone
    {
        $timezone = date_default_timezone_get() !== 'UTC' ? 'UTC' : 'Europe/Moscow';
        return new \DateTimeZone($timezone);
    }

    private static function assertDateEquals(\DateTimeInterface $expected, \DateTimeInterface $actual): void
    {
        self::assertEquals($expected->getTimestamp(), $actual->getTimestamp());
    }

    private static function assertDateNotEquals(\DateTimeInterface $expected, \DateTimeInterface $actual): void
    {
        self::assertNotEquals($expected->getTimestamp(), $actual->getTimestamp());
    }
}
