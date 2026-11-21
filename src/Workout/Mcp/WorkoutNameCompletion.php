<?php

declare(strict_types=1);

namespace App\Workout\Mcp;

use App\Workout\Workout;
use App\Workout\WorkoutRepository;
use Mcp\Capability\Completion\ProviderInterface;

final readonly class WorkoutNameCompletion implements ProviderInterface
{
    public function __construct(
        private WorkoutRepository $workouts,
    ) {
    }

    /**
     * @return list<string>
     */
    public function getCompletions(string $currentValue): array
    {
        $names = array_map(static fn (Workout $workout): string => $workout->name, $this->workouts->all());
        $typed = mb_strtolower(trim($currentValue));

        return array_values(array_filter($names, static fn (string $name): bool => str_starts_with(mb_strtolower($name), $typed)));
    }
}
