<?php

declare(strict_types=1);

namespace App\Workout\Mcp;

use App\Shared\Limits;
use App\Shared\Mcp\RefusesPolitely;
use App\Workout\Action\PlanExercise;
use Mcp\Capability\Attribute\McpTool;
use Mcp\Capability\Attribute\Schema;
use Mcp\Schema\ToolAnnotations;

#[McpTool(
    name: 'plan_exercise',
    title: 'Add an exercise to a workout',
    description: 'Append one exercise to a workout, with how many sets and what to aim for.',
    annotations: new ToolAnnotations(readOnlyHint: false, destructiveHint: false, idempotentHint: false),
    outputSchema: WorkoutSummary::SCHEMA,
)]
final readonly class PlanExerciseTool
{
    use RefusesPolitely;

    public function __construct(
        private PlanExercise $plan,
    ) {
    }

    public function __invoke(
        #[Schema(description: 'Name of the workout to add to')]
        string $workout,
        #[Schema(description: 'Exercise id from search_exercises, e.g. bench-press')]
        string $exercise,
        #[Schema(description: 'How many sets to perform', minimum: 1, maximum: Limits::MAX_SETS)]
        int $sets,
        #[Schema(description: 'Repetitions per set', minimum: 1, maximum: Limits::MAX_REPS)]
        ?int $reps = null,
        #[Schema(description: 'Seconds per set, for holds', minimum: 1, maximum: Limits::MAX_SECONDS)]
        ?int $seconds = null,
        #[Schema(description: 'Load in kilograms, omitted for bodyweight work', minimum: 0, maximum: Limits::MAX_WEIGHT)]
        ?float $weight = null,
    ): WorkoutSummary {
        return $this->refusing(fn (): WorkoutSummary => WorkoutSummary::of(($this->plan)($workout, $exercise, $sets, $reps, $seconds, $weight)));
    }
}
