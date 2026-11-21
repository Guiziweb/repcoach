<?php

declare(strict_types=1);

namespace App\Workout\Mcp;

use App\Shared\Mcp\RefusesPolitely;
use App\Workout\Action\RemoveExercise;
use Mcp\Capability\Attribute\McpTool;
use Mcp\Capability\Attribute\Schema;
use Mcp\Schema\ToolAnnotations;

#[McpTool(
    name: 'remove_exercise',
    title: 'Remove an exercise from a workout',
    description: 'Drop one planned line. Identify it by its position, as shown by list_workouts, because the same exercise may be planned twice.',
    annotations: new ToolAnnotations(readOnlyHint: false, destructiveHint: true, idempotentHint: false),
    outputSchema: WorkoutSummary::SCHEMA,
)]
final readonly class RemoveExerciseTool
{
    use RefusesPolitely;

    public function __construct(
        private RemoveExercise $remove,
    ) {
    }

    public function __invoke(
        #[Schema(description: 'Name of the workout to edit')]
        string $workout,
        #[Schema(description: 'Position of the line to drop, starting at 1', minimum: 1)]
        int $position,
    ): WorkoutSummary {
        return $this->refusing(fn (): WorkoutSummary => WorkoutSummary::of(($this->remove)($workout, $position)));
    }
}
