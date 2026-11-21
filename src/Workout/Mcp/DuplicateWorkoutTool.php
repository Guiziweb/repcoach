<?php

declare(strict_types=1);

namespace App\Workout\Mcp;

use App\Shared\Limits;
use App\Shared\Mcp\RefusesPolitely;
use App\Workout\Action\DuplicateWorkout;
use App\Workout\WorkoutRepository;
use Mcp\Capability\Attribute\McpTool;
use Mcp\Capability\Attribute\Schema;
use Mcp\Schema\ToolAnnotations;

#[McpTool(
    name: 'duplicate_workout',
    title: 'Duplicate a workout',
    description: 'Copy a saved workout, with all its exercises, under a new name. The quickest way to build a variation.',
    annotations: new ToolAnnotations(readOnlyHint: false, destructiveHint: false, idempotentHint: false),
    outputSchema: WorkoutSummary::LIST_SCHEMA,
)]
final readonly class DuplicateWorkoutTool
{
    use RefusesPolitely;

    public function __construct(
        private DuplicateWorkout $duplicate,
        private WorkoutRepository $workouts,
    ) {
    }

    /**
     * @return array{workouts: list<WorkoutSummary>}
     */
    public function __invoke(
        #[Schema(description: 'Name of the workout to copy')]
        string $workout,
        #[Schema(description: 'Name for the copy', minLength: 1, maxLength: Limits::MAX_NAME)]
        string $name,
    ): array {
        return $this->refusing(function () use ($workout, $name): array {
            ($this->duplicate)($workout, $name);

            return WorkoutSummary::listOf($this->workouts->all());
        });
    }
}
