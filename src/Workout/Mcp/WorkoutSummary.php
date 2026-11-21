<?php

declare(strict_types=1);

namespace App\Workout\Mcp;

use App\Workout\Workout;
use App\Workout\WorkoutExercise;

final readonly class WorkoutSummary
{
    public const array SCHEMA = [
        'type' => 'object',
        'required' => ['name', 'exercises'],
        'properties' => [
            'name' => ['type' => 'string'],
            'exercises' => ['type' => 'array', 'items' => PlannedExerciseView::SCHEMA],
        ],
    ];

    public const array LIST_SCHEMA = [
        'type' => 'object',
        'required' => ['workouts'],
        'properties' => ['workouts' => ['type' => 'array', 'items' => self::SCHEMA]],
    ];

    /**
     * @param list<Workout> $workouts
     *
     * @return array{workouts: list<self>}
     */
    public static function listOf(array $workouts): array
    {
        return ['workouts' => array_map(self::of(...), $workouts)];
    }

    /**
     * @param list<PlannedExerciseView> $exercises
     */
    private function __construct(
        public string $name,
        public array $exercises,
    ) {
    }

    public static function of(Workout $workout): self
    {
        $ordered = $workout->getExercises()->toArray();
        usort($ordered, static fn (WorkoutExercise $a, WorkoutExercise $b): int => $a->position <=> $b->position);

        return new self($workout->name, array_map(PlannedExerciseView::of(...), $ordered));
    }
}
