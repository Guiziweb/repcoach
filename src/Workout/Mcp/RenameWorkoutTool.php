<?php

declare(strict_types=1);

namespace App\Workout\Mcp;

use App\Shared\Limits;
use App\Shared\Mcp\RefusesPolitely;
use App\Workout\Action\RenameWorkout;
use Mcp\Capability\Attribute\McpTool;
use Mcp\Capability\Attribute\Schema;
use Mcp\Schema\ToolAnnotations;

#[McpTool(
    name: 'rename_workout',
    title: 'Rename a workout',
    description: 'Give a saved workout another name. Sessions already recorded keep the name they were run under.',
    annotations: new ToolAnnotations(readOnlyHint: false, destructiveHint: false, idempotentHint: true),
    outputSchema: WorkoutSummary::SCHEMA,
)]
final readonly class RenameWorkoutTool
{
    use RefusesPolitely;

    public function __construct(
        private RenameWorkout $rename,
    ) {
    }

    #[Schema(additionalProperties: false)]
    public function __invoke(
        #[Schema(description: 'Name of the workout to rename')]
        string $workout,
        #[Schema(description: 'The new name', minLength: 1, maxLength: Limits::MAX_NAME)]
        string $name,
    ): WorkoutSummary {
        return $this->refusing(fn (): WorkoutSummary => WorkoutSummary::of(($this->rename)($workout, $name)));
    }
}
