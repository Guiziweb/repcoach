<?php

declare(strict_types=1);

namespace App\Workout;

use App\Shared\Check;

final readonly class WorkoutNames
{
    public function __construct(
        private Check $check,
        private WorkoutRepository $workouts,
    ) {
    }

    public function free(string $name, ?Workout $besides = null): string
    {
        $checked = new WorkoutName($name);
        ($this->check)($checked);

        return $this->workouts->ensureFree($checked->value, $besides);
    }
}
