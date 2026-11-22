<?php

declare(strict_types=1);

namespace App\Tests\Web\Program\Coach;

use App\Tests\ExerciseFixture;
use App\Web\Program\Coach\ProgramDescription;
use PHPUnit\Framework\TestCase;

final class ProgramDescriptionTest extends TestCase
{
    public function testItListsTheLinesOfTheWorkoutOnScreen(): void
    {
        self::assertSame(
            "The workout \"Pecs\":\n1. Barbell Bench Press, 4 sets of 8 reps at 60 kg",
            ProgramDescription::of(ExerciseFixture::workout('Pecs')),
        );
    }

    public function testWithoutAWorkoutTheScreenIsEmpty(): void
    {
        self::assertSame('The workouts screen, with no workout on it.', ProgramDescription::of(null));
    }
}
