<?php

declare(strict_types=1);

namespace App\Workout\Mcp;

use App\Workout\WorkoutRepository;
use Mcp\Capability\Attribute\McpTool;
use Mcp\Schema\ToolAnnotations;

#[McpTool(
    name: 'list_workouts',
    title: 'List saved workouts',
    description: 'Every workout that can be started, with the exercises it plans.',
    annotations: new ToolAnnotations(readOnlyHint: true, idempotentHint: true, openWorldHint: false),
    outputSchema: WorkoutSummary::LIST_SCHEMA,
)]
final readonly class ListWorkoutsTool
{
    public function __construct(
        private WorkoutRepository $workouts,
    ) {
    }

    /**
     * @return array{workouts: list<WorkoutSummary>}
     */
    public function __invoke(): array
    {
        return WorkoutSummary::listOf($this->workouts->all());
    }
}
