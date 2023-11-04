<?php /** @noinspection DevelopmentDependenciesUsageInspection */

declare(strict_types=1);

namespace Flying\Date\PHPUnit\Extension\Subscriber;

use Flying\Date\Date;
use PHPUnit\Event\Test\Errored;
use PHPUnit\Event\Test\ErroredSubscriber;
use PHPUnit\Event\Test\Failed;
use PHPUnit\Event\Test\FailedSubscriber;
use PHPUnit\Event\Test\MarkedIncomplete;
use PHPUnit\Event\Test\MarkedIncompleteSubscriber;
use PHPUnit\Event\Test\Passed;
use PHPUnit\Event\Test\PassedSubscriber;
use PHPUnit\Event\Test\PreparationFailed;
use PHPUnit\Event\Test\PreparationFailedSubscriber;
use PHPUnit\Event\Test\Skipped;
use PHPUnit\Event\Test\SkippedSubscriber;

class ReleaseDateAdjustment implements
    PassedSubscriber,
    MarkedIncompleteSubscriber,
    SkippedSubscriber,
    PreparationFailedSubscriber,
    FailedSubscriber,
    ErroredSubscriber
{
    public function notify(Passed|MarkedIncomplete|Skipped|PreparationFailed|Failed|Errored $event): void
    {
        $this->release();
    }

    private function release(): void
    {
        Date::allowAdjustment(false);
        Date::setTimezone();
        Date::adjust();
    }
}
