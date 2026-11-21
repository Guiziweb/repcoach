<?php

declare(strict_types=1);

namespace App\Workout\Mcp;

use App\Shared\Mcp\RefusesPolitely;
use App\Workout\Action\MoveExercise;
use Mcp\Capability\Attribute\McpTool;
use Mcp\Capability\Attribute\Schema;
use Mcp\Schema\ToolAnnotations;

#[McpTool(
    name: 'move_exercise',
    title: 'Reorder a workout',
    description: 'Move one planned line to another position. The other lines shift to keep the order contiguous.',
    annotations: new ToolAnnotations(readOnlyHint: false, destructiveHint: false, idempotentHint: false),
    outputSchema: WorkoutSummary::SCHEMA,
)]
final readonly class MoveExerciseTool
{
    use RefusesPolitely;

    public function __construct(
        private MoveExercise $move,
    ) {
    }

    #[Schema(additionalProperties: false)]
    public function __invoke(
        #[Schema(description: 'Name of the workout to reorder')]
        string $workout,
        #[Schema(description: 'Current position of the line to move', minimum: 1)]
        int $position,
        #[Schema(description: 'Position it should take', minimum: 1)]
        int $to,
    ): WorkoutSummary {
        return $this->refusing(fn (): WorkoutSummary => WorkoutSummary::of(($this->move)($workout, $position, $to)));
    }
}
