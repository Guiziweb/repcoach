<?php

declare(strict_types=1);

namespace App\Session\Mcp;

use App\Session\SessionExercise;
use App\Workout\Mcp\PlannedExerciseView;

final readonly class SessionPlanView
{
    public const array SCHEMA = [
        'type' => 'object',
        'required' => [...PlannedExerciseView::SCHEMA['required'], 'done'],
        'properties' => [...PlannedExerciseView::SCHEMA['properties'], 'done' => ['type' => 'integer']],
    ];

    private function __construct(
        public int $position,
        public string $exercise,
        public string $exerciseId,
        public int $sets,
        public ?int $reps,
        public ?int $seconds,
        public ?float $weight,
        public int $done,
    ) {
    }

    public static function of(SessionExercise $planned, int $done): self
    {
        return new self(
            $planned->position,
            $planned->exercise->name,
            $planned->exercise->id,
            $planned->sets,
            $planned->reps,
            $planned->seconds,
            $planned->weight,
            $done,
        );
    }
}
