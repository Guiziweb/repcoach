<?php

declare(strict_types=1);

namespace App\Workout\Mcp;

use App\Shared\Mcp\RefusesPolitely;
use App\Workout\WorkoutRepository;
use Mcp\Capability\Attribute\McpTool;
use Mcp\Capability\Attribute\Schema;
use Mcp\Schema\ToolAnnotations;

#[McpTool(
    name: 'show_workout',
    title: 'Show one workout',
    description: 'Everything planned in a single workout, with the position of each line.',
    annotations: new ToolAnnotations(readOnlyHint: true, idempotentHint: true, openWorldHint: false),
    outputSchema: WorkoutSummary::SCHEMA,
)]
final readonly class ShowWorkoutTool
{
    use RefusesPolitely;

    public function __construct(
        private WorkoutRepository $workouts,
    ) {
    }

    #[Schema(additionalProperties: false)]
    public function __invoke(
        #[Schema(description: 'Name of the workout to show')]
        string $workout,
    ): WorkoutSummary {
        return $this->refusing(fn (): WorkoutSummary => WorkoutSummary::of($this->workouts->get($workout)));
    }
}
