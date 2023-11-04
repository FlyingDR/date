<?php /** @noinspection DevelopmentDependenciesUsageInspection */

declare(strict_types=1);

namespace Flying\Date\PHPUnit\Extension;

use Flying\Date\PHPUnit\Extension\Subscriber\ReleaseDateAdjustment;
use Flying\Date\PHPUnit\Extension\Subscriber\SetupDateAdjustment;
use PHPUnit\Runner\Extension\Extension;
use PHPUnit\Runner\Extension\Facade;
use PHPUnit\Runner\Extension\ParameterCollection;
use PHPUnit\TextUI\Configuration\Configuration;

class DateExtension implements Extension
{
    public function bootstrap(Configuration $configuration, Facade $facade, ParameterCollection $parameters): void
    {
        $facade->registerSubscribers(
            new SetupDateAdjustment(),
            new ReleaseDateAdjustment(),
        );
    }
}
