<?php

declare(strict_types=1);

namespace App\Tests\Workout;

use App\Tests\ExerciseFixture;
use App\Workout\Workout;
use App\Workout\WorkoutExercise;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Workout::class)]
#[CoversClass(WorkoutExercise::class)]
final class WorkoutTest extends TestCase
{
    public function testItNumbersExercisesInTheOrderTheyAreAdded(): void
    {
        $workout = new Workout('Pecs');
        $bench = $this->plan($workout, 'bench-press');
        $plank = $this->plan($workout, 'plank');

        self::assertSame(1, $bench->position);
        self::assertSame(2, $plank->position);
    }

    public function testRemovingAnExerciseClosesTheGapItLeaves(): void
    {
        $workout = new Workout('Pecs');
        $bench = $this->plan($workout, 'bench-press');
        $plank = $this->plan($workout, 'plank');
        $rows = $this->plan($workout, 'rows');

        $workout->remove($plank);

        self::assertSame(1, $bench->position);
        self::assertSame(2, $rows->position);
        self::assertSame(2, $workout->getExercises()->count());
        self::assertSame(3, $workout->nextPosition());
    }

    public function testAChangedLineKeepsItsPlaceAndItsExercise(): void
    {
        $workout = new Workout('Pecs');
        $this->plan($workout, 'bench-press');
        $plank = $this->plan($workout, 'plank');

        $plank->change(sets: 5, reps: null, seconds: 60, weight: null);

        self::assertSame(2, $plank->position);
        self::assertSame('plank', $plank->exercise->id);
        self::assertSame(5, $plank->sets);
        self::assertSame(60, $plank->seconds);
    }

    public function testAChangeSwapsRepetitionsForADuration(): void
    {
        $planned = $this->plan(new Workout('Pecs'), 'bench-press');

        $planned->change(sets: 3, reps: null, seconds: 45, weight: null);

        self::assertNull($planned->reps);
        self::assertSame(45, $planned->seconds);
        self::assertNull($planned->weight);
    }

    private function plan(Workout $workout, string $exerciseId): WorkoutExercise
    {
        return new WorkoutExercise(
            $workout,
            ExerciseFixture::exercise(id: $exerciseId, name: ucfirst($exerciseId)),
            $workout->nextPosition(),
            sets: 4,
            reps: 8,
            weight: 60.0,
        );
    }
}
