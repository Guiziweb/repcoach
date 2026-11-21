<?php

declare(strict_types=1);

namespace App\Tests\Shared;

use App\Shared\Effort;
use App\Shared\ExerciseEffort;
use App\Shared\Limits;
use App\Shared\PlannedEffort;
use App\Shared\Refused;
use App\Tests\Checker;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class EffortTest extends TestCase
{
    #[DataProvider('refusedEfforts')]
    public function testItRefusesWhatNoOneCouldPerform(Effort $effort, string $reason): void
    {
        $this->expectException(Refused::class);
        $this->expectExceptionMessageIsOrContains($reason);

        Checker::make()($effort);
    }

    /**
     * @return iterable<string, array{Effort, string}>
     */
    public static function refusedEfforts(): iterable
    {
        yield 'nothing given' => [new Effort(null, null, null), 'either repetitions or a duration'];
        yield 'both given' => [new Effort(8, 45, null), 'not both'];
        yield 'no repetition' => [new Effort(0, null, null), 'Repetitions must be between 1 and 200'];
        yield 'too many repetitions' => [new Effort(Limits::MAX_REPS + 1, null, 20.0), 'Repetitions must be between'];
        yield 'no second' => [new Effort(null, 0, null), 'Seconds must be between 1 and 3600'];
        yield 'a hold that is too long' => [new Effort(null, Limits::MAX_SECONDS + 1, null), 'Seconds must be between'];
        yield 'a negative weight' => [new Effort(8, null, -1.0), 'The weight must be between 0 and 1000'];
        yield 'a weight that is too heavy' => [new Effort(8, null, Limits::MAX_WEIGHT + 1), 'The weight must be between'];
        yield 'an overflowing volume' => [new Effort(\PHP_INT_MAX, null, 1.0e308), 'Repetitions must be between'];
        yield 'no set at all' => [new PlannedEffort(0, 8, null, null), 'Sets must be between 1 and 50'];
        yield 'too many sets' => [new PlannedEffort(Limits::MAX_SETS + 1, 8, null, null), 'Sets must be between'];
        yield 'a lift without its weight' => [new ExerciseEffort(8, null, null, true), 'Give the weight in kilograms'];
        yield 'a planned line with both' => [new PlannedEffort(3, 8, 45, null), 'not both'];
    }

    #[DataProvider('acceptedEfforts')]
    public function testItAcceptsEveryLimitItself(Effort $effort): void
    {
        Checker::make()($effort);

        $this->addToAssertionCount(1);
    }

    /**
     * @return iterable<string, array{Effort}>
     */
    public static function acceptedEfforts(): iterable
    {
        yield 'the heaviest lift' => [new Effort(Limits::MAX_REPS, null, Limits::MAX_WEIGHT)];
        yield 'the longest hold' => [new Effort(null, Limits::MAX_SECONDS, null)];
        yield 'bodyweight work' => [new Effort(12, null, null)];
        yield 'repetitions with no load' => [new ExerciseEffort(12, null, null, false)];
        yield 'a lift with its weight' => [new ExerciseEffort(8, null, 60.0, true)];
        yield 'a hold on a loaded exercise' => [new ExerciseEffort(null, 30, null, true)];
        yield 'the longest plan' => [new PlannedEffort(Limits::MAX_SETS, 1, null, 0.0)];
    }
}
