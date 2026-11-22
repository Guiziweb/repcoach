<?php

declare(strict_types=1);

namespace App\Web\Program;

use App\Web\Program\View\ProgramView;
use App\Workout\Workout;
use App\Workout\WorkoutRepository;

final readonly class ProgramPages
{
    public function __construct(
        private WorkoutRepository $workouts,
    ) {
    }

    public function named(string $name): ?ProgramView
    {
        $wanted = $this->workouts->named($name);
        $all = $this->workouts->all();
        $index = null === $wanted ? false : array_search($wanted, $all, true);

        return false === $index ? null : self::at($all, $index);
    }

    public function first(): ?ProgramView
    {
        $all = $this->workouts->all();

        return [] === $all ? null : self::at($all, 0);
    }

    /**
     * @param non-empty-list<Workout> $all
     */
    private static function at(array $all, int $index): ProgramView
    {
        $count = \count($all);

        return new ProgramView($all[$index], $index + 1, $count, $all[($index - 1 + $count) % $count], $all[($index + 1) % $count]);
    }
}
