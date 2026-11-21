<?php

declare(strict_types=1);

namespace App\Workout\Mcp;

use App\Shared\Mcp\RefusesPolitely;
use App\Workout\Action\DeleteWorkout;
use App\Workout\WorkoutRepository;
use Mcp\Capability\Attribute\McpTool;
use Mcp\Capability\Attribute\Schema;
use Mcp\Schema\ToolAnnotations;

#[McpTool(
    name: 'delete_workout',
    title: 'Delete a workout',
    description: 'Remove a saved workout. Sessions already performed are kept, with the name they were run under.',
    annotations: new ToolAnnotations(readOnlyHint: false, destructiveHint: true, idempotentHint: false),
    outputSchema: WorkoutSummary::LIST_SCHEMA,
)]
final readonly class DeleteWorkoutTool
{
    use RefusesPolitely;

    public function __construct(
        private DeleteWorkout $delete,
        private WorkoutRepository $workouts,
    ) {
    }

    /**
     * @return array{workouts: list<WorkoutSummary>}
     */
    #[Schema(additionalProperties: false)]
    public function __invoke(
        #[Schema(description: 'Name of the workout to remove')]
        string $workout,
    ): array {
        return $this->refusing(function () use ($workout): array {
            ($this->delete)($workout);

            return WorkoutSummary::listOf($this->workouts->all());
        });
    }
}
