<?php

declare(strict_types=1);

namespace App\Workout\Mcp;

use App\Shared\Limits;
use App\Shared\Mcp\RefusesPolitely;
use App\Workout\Action\CreateWorkout;
use Mcp\Capability\Attribute\McpTool;
use Mcp\Capability\Attribute\Schema;
use Mcp\Schema\ToolAnnotations;

#[McpTool(
    name: 'create_workout',
    title: 'Create a workout',
    description: 'Save a workout so it can be started later. Add its exercises with plan_exercise.',
    annotations: new ToolAnnotations(readOnlyHint: false, destructiveHint: false, idempotentHint: false),
    outputSchema: WorkoutSummary::SCHEMA,
)]
final readonly class CreateWorkoutTool
{
    use RefusesPolitely;

    public function __construct(
        private CreateWorkout $create,
    ) {
    }

    public function __invoke(
        #[Schema(description: 'How you will call it, e.g. "Pecs"', minLength: 1, maxLength: Limits::MAX_NAME)]
        string $name,
    ): WorkoutSummary {
        return $this->refusing(fn (): WorkoutSummary => WorkoutSummary::of(($this->create)($name)));
    }
}
