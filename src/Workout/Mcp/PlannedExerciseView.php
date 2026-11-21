<?php

declare(strict_types=1);

namespace App\Workout\Mcp;

use App\Workout\WorkoutExercise;

final readonly class PlannedExerciseView
{
    public const array SCHEMA = [
        'type' => 'object',
        'required' => ['position', 'exercise', 'exerciseId', 'sets', 'reps', 'seconds', 'weight'],
        'properties' => [
            'position' => ['type' => 'integer'],
            'exercise' => ['type' => 'string'],
            'exerciseId' => ['type' => 'string'],
            'sets' => ['type' => 'integer'],
            'reps' => ['type' => ['integer', 'null']],
            'seconds' => ['type' => ['integer', 'null']],
            'weight' => ['type' => ['number', 'null']],
        ],
    ];

    private function __construct(
        public int $position,
        public string $exercise,
        public string $exerciseId,
        public int $sets,
        public ?int $reps,
        public ?int $seconds,
        public ?float $weight,
    ) {
    }

    public static function of(WorkoutExercise $planned): self
    {
        return new self(
            $planned->position,
            $planned->exercise->name,
            $planned->exercise->id,
            $planned->sets,
            $planned->reps,
            $planned->seconds,
            $planned->weight,
        );
    }
}
