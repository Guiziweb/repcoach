<?php

declare(strict_types=1);

namespace App\Workout\Mcp;

use App\Shared\Limits;
use App\Shared\Mcp\RefusesPolitely;
use App\Workout\Action\ChangeExercise;
use Mcp\Capability\Attribute\McpTool;
use Mcp\Capability\Attribute\Schema;
use Mcp\Schema\ToolAnnotations;

#[McpTool(
    name: 'change_exercise',
    title: 'Change a planned exercise',
    description: 'Rewrite one planned line in place, keeping its exercise and its position. Every field is replaced, so pass the ones to keep as well.',
    annotations: new ToolAnnotations(readOnlyHint: false, destructiveHint: false, idempotentHint: true),
    outputSchema: WorkoutSummary::SCHEMA,
)]
final readonly class ChangeExerciseTool
{
    use RefusesPolitely;

    public function __construct(
        private ChangeExercise $change,
    ) {
    }

    public function __invoke(
        #[Schema(description: 'Name of the workout to edit')]
        string $workout,
        #[Schema(description: 'Position of the line to rewrite, starting at 1', minimum: 1)]
        int $position,
        #[Schema(description: 'How many sets to perform', minimum: 1, maximum: Limits::MAX_SETS)]
        int $sets,
        #[Schema(description: 'Repetitions per set', minimum: 1, maximum: Limits::MAX_REPS)]
        ?int $reps = null,
        #[Schema(description: 'Seconds per set, for holds', minimum: 1, maximum: Limits::MAX_SECONDS)]
        ?int $seconds = null,
        #[Schema(description: 'Load in kilograms, omitted for bodyweight work', minimum: 0, maximum: Limits::MAX_WEIGHT)]
        ?float $weight = null,
    ): WorkoutSummary {
        return $this->refusing(fn (): WorkoutSummary => WorkoutSummary::of(($this->change)($workout, $position, $sets, $reps, $seconds, $weight)));
    }
}
