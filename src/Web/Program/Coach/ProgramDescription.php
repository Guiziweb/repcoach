<?php

declare(strict_types=1);

namespace App\Web\Program\Coach;

use App\Web\Coach\Screen\DescribesScreen;
use App\Web\Coach\Screen\EffortInWords;
use App\Workout\Workout;
use App\Workout\WorkoutExercise;
use App\Workout\WorkoutRepository;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;

#[AsTaggedItem('program')]
#[AsTaggedItem('programs')]
final readonly class ProgramDescription implements DescribesScreen
{
    public function __construct(
        private WorkoutRepository $workouts,
    ) {
    }

    public function describe(array $parameters): string
    {
        $name = $parameters['name'] ?? null;

        return self::of(\is_string($name) ? $this->workouts->named($name) : null);
    }

    public static function of(?Workout $shown): string
    {
        if (null === $shown) {
            return 'The workouts screen, with no workout on it.';
        }

        $lines = array_map(
            static fn (WorkoutExercise $line): string => \sprintf(
                '%d. %s, %d sets of %s',
                $line->position,
                $line->exercise->name,
                $line->sets,
                EffortInWords::of($line->reps, $line->seconds, $line->weight),
            ),
            $shown->getExercises()->toArray(),
        );

        return \sprintf("The workout \"%s\":\n%s", $shown->name, implode("\n", $lines));
    }
}
