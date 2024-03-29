<?php /** @noinspection PhpUnhandledExceptionInspection */

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

        $timezone = self::getNonDefaultTimezone();
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
        self::assertEquals(self::getDefaultTimezone(), $now->getTimezone());

        Date::setTimezone(self::getNonDefaultTimezone());
        $now = Date::now();
        self::assertDateEquals(new \DateTimeImmutable(), $now);
        self::assertEquals(self::getNonDefaultTimezone(), $now->getTimezone());
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
        $tz1 = self::getDefaultTimezone();
        $tz2 = self::getNonDefaultTimezone();

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
        self::assertEquals(self::getDefaultTimezone(), $date->getTimezone());

        Date::setTimezone(self::getNonDefaultTimezone());
        $date = Date::from($reference);
        self::assertEquals(self::getNonDefaultTimezone(), $date->getTimezone());

        Date::setTimezone(self::getDefaultTimezone()->getName());
        $date = Date::from($reference);
        self::assertEquals(self::getDefaultTimezone(), $date->getTimezone());

        Date::setTimezone();
        $date = Date::from($reference);
        self::assertEquals(self::getDefaultTimezone(), $date->getTimezone());

        $timezone = self::getNonDefaultTimezone();
        $date = Date::from($reference, $timezone);
        self::assertEquals($timezone, $date->getTimezone());

        $timezone = self::getNonDefaultTimezone();
        $date = Date::from($reference, $timezone->getName());
        self::assertEquals($timezone, $date->getTimezone());
    }

    /**
     * @dataProvider dpDatesFromFormatWithoutTimezone
     */
    public function testDatesFromFormatWithoutTimezone(string $format, string $datetime): void
    {
        $created = Date::fromFormat($format, $datetime);
        self::assertEquals($datetime, $created->format($format));
    }

    public static function dpDatesFromFormatWithoutTimezone(): array
    {
        return [
            ['Y-m-d', '2022-08-01'],
            ['Y-m-d H:i:s', '2022-08-01 12:23:34'],
        ];
    }

    /**
     * @dataProvider dpDatesFromFormatWithEmbeddedTimezone
     */
    public function testDatesFromFormatWithEmbeddedTimezone(string $format, string $datetime, \DateTimeZone $timezone): void
    {
        $created = Date::fromFormat($format, $datetime, $timezone);
        self::assertEquals($datetime, $created->format($format));
        self::assertEquals($timezone, $created->getTimezone());
    }

    public static function dpDatesFromFormatWithEmbeddedTimezone(): array
    {
        return [
            [\DateTimeInterface::ATOM, '2022-08-01T12:23:34-05:00', new \DateTimeZone('-05:00')],
            [\DateTimeInterface::ATOM, '2022-08-01T12:23:34+03:00', new \DateTimeZone('+03:00')],
        ];
    }

    /**
     * @dataProvider dpDatesFromFormatWithExplicitlyGivenTimezone
     */
    public function testDatesFromFormatWithExplicitlyGivenTimezone(string $format, string $datetime, \DateTimeZone $timezone): void
    {
        $created = Date::fromFormat($format, $datetime, $timezone);
        self::assertEquals(\DateTimeImmutable::createFromFormat($format, $datetime, $timezone), $created);
        self::assertEquals($timezone, $created->getTimezone());
    }

    public static function dpDatesFromFormatWithExplicitlyGivenTimezone(): array
    {
        return [
            ['Y-m-d H:i:s', '2022-08-01 12:23:34', self::getDefaultTimezone()],
            ['Y-m-d H:i:s', '2022-08-01 12:23:34', self::getNonDefaultTimezone()],
            [\DateTimeInterface::ATOM, '2022-08-01T12:23:34Z', new \DateTimeZone('America/New_York')],
            [\DateTimeInterface::ATOM, '2022-08-01T12:23:34+03:00', new \DateTimeZone('Asia/Singapore')],
        ];
    }

    public function testCreatingInvalidDateFromFormat(): void
    {
        self::assertFalse(Date::fromFormat('invalid-format', '2022-08-01'));
        self::assertFalse(Date::fromFormat(\DateTimeInterface::ATOM, 'invalid-date'));
    }

    public function testCreatingTimeAdjustmentFromDifferentFormats(): void
    {
        Date::adjust();
        self::assertNull(Date::getAdjustment());

        $interval = new \DateInterval('PT1H');
        Date::adjust((new \DateTimeImmutable())->add($interval));
        self::assertNotSame($interval, Date::getAdjustment());
        self::assertIntervalEquals($interval, Date::getAdjustment());

        $interval = new \DateInterval('P1DT2H3M4S');
        Date::adjust((new \DateTimeImmutable())->sub($interval));
        self::assertIntervalEquals($interval, Date::getAdjustment());

        Date::adjust($interval);
        self::assertSame($interval, Date::getAdjustment());

        $interval = 'P1M2DT3H4M';
        Date::adjust($interval);
        self::assertIntervalEquals(new \DateInterval($interval), Date::getAdjustment());

        $interval = '2022-08-01 12:23:34';
        Date::adjust($interval);
        self::assertIntervalEquals(
            (\DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $interval))->diff(new \DateTimeImmutable()),
            Date::getAdjustment(),
        );

        $interval = '+3 weeks';
        Date::adjust($interval);
        self::assertIntervalEquals(new \DateInterval('P3W'), Date::getAdjustment());

        $interval = '-5 days';
        Date::adjust($interval);
        $interval = new \DateInterval('P5D');
        $interval->invert = 1;
        self::assertIntervalEquals($interval, Date::getAdjustment());
    }

    /**
     * @dataProvider dpInvalidIntervalFormatStringsForAdjustmentThrowsException
     * @throws \Exception
     */
    public function testInvalidIntervalFormatStringsForAdjustmentThrowsException(string $adjustment): void
    {
        $this->expectException(\Exception::class);
        Date::adjust($adjustment);
    }

    public static function dpInvalidIntervalFormatStringsForAdjustmentThrowsException(): array
    {
        return [
            ['P1X2Y3Z'],
            ['invalid relative date definition'],
        ];
    }

    #[AdjustableDate]
    public function testDateAdjustmentAppliesToPrimaryDateGeneratorMethod(): void
    {
        $reference = $this->getReferenceDate();
        $diff = (new \DateTimeImmutable())->diff($reference);
        $diff->f = 0;

        Date::adjust($reference);
        $now = Date::now();
        self::assertNotSame($reference, $now);
        self::assertDateEquals($reference, $now);
        self::assertDateNotEquals(new \DateTimeImmutable(), $now);
        self::assertDateEquals((new \DateTimeImmutable())->add($diff), $now);
        $date = Date::from($reference);
        self::assertDateEquals($reference->add($diff), $date);
        $date = Date::fromFormat(\DateTimeInterface::ATOM, $reference->format(\DateTimeInterface::ATOM));
        self::assertDateEquals($reference->add($diff), $date);

        $interval = new \DateInterval('P1DT2H3M4S');
        Date::adjust($interval);
        self::assertEquals($interval, Date::getAdjustment());
        $now = Date::now();
        self::assertDateNotEquals(new \DateTimeImmutable(), $now);
        self::assertDateEquals((new \DateTimeImmutable())->add($interval), $now);
        $date = Date::from($reference);
        self::assertDateEquals($reference->add($interval), $date);
        $date = Date::fromFormat(\DateTimeInterface::ATOM, $reference->format(\DateTimeInterface::ATOM));
        self::assertDateEquals($reference->add($interval), $date);

        Date::adjust();
        self::assertNull(Date::getAdjustment());
        $now = Date::now();
        self::assertDateEquals(new \DateTimeImmutable(), $now);
        $date = Date::from($reference);
        self::assertDateEquals($reference, $date);
        $date = Date::fromFormat(\DateTimeInterface::ATOM, $reference->format(\DateTimeInterface::ATOM));
        self::assertDateEquals($reference, $date);
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

    #[AdjustableDate]
    public function testAdjustedDateAlwaysHaveZeroMicroseconds(): void
    {
        Date::adjust('1 minute');
        // It is necessary to run lots of attempts because it is a pretty rare case
        for ($i = 0; $i < 100; $i++) {
            $now = Date::now();
            self::assertEquals('000000', $now->format('u'));
            usleep(100);
        }
    }

    private function getReferenceDate(): \DateTimeImmutable
    {
        return \DateTimeImmutable::createFromFormat(\DateTimeInterface::ATOM, '2022-08-01T12:23:34Z', self::getDefaultTimezone());
    }

    private static function getDefaultTimezone(): \DateTimeZone
    {
        return new \DateTimeZone(date_default_timezone_get());
    }

    private static function getNonDefaultTimezone(): \DateTimeZone
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

    private static function assertIntervalEquals(\DateInterval $expected, \DateInterval $actual): void
    {
        self::assertEquals($expected->format('%Y%M%D%H%I%S'), $actual->format('%Y%M%D%H%I%S'));
    }
}
