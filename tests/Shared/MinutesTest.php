<?php

declare(strict_types=1);

namespace App\Tests\Shared;

use App\Shared\Limits;
use App\Shared\Minutes;
use App\Shared\Refused;
use App\Tests\Checker;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class MinutesTest extends TestCase
{
    #[DataProvider('impossibleDurations')]
    public function testItRefusesAWorkoutThatCannotHaveHappened(int $minutes): void
    {
        $this->expectException(Refused::class);
        $this->expectExceptionMessageIsOrContains('A workout lasts between 1 and 360 minutes.');

        Checker::make()($minutes, new Minutes());
    }

    /**
     * @return iterable<string, array{int}>
     */
    public static function impossibleDurations(): iterable
    {
        yield 'none' => [0];
        yield 'negative' => [-5];
        yield 'one minute too long' => [Limits::MAX_MINUTES + 1];
        yield 'the largest integer' => [\PHP_INT_MAX];
    }

    public function testNoDurationAtAllIsFine(): void
    {
        Checker::make()(null, new Minutes());

        $this->addToAssertionCount(1);
    }
}
