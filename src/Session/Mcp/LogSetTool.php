<?php

declare(strict_types=1);

namespace App\Session\Mcp;

use App\Session\Action\LogSet;
use App\Shared\Limits;
use App\Shared\Mcp\RefusesPolitely;
use Mcp\Capability\Attribute\McpTool;
use Mcp\Capability\Attribute\Schema;
use Mcp\Schema\ToolAnnotations;
use Psr\Clock\ClockInterface;

#[McpTool(
    name: 'log_set',
    title: 'Log a set',
    description: 'Record one set in the running session. Give repetitions for a lift, or seconds for a hold.',
    annotations: new ToolAnnotations(readOnlyHint: false, destructiveHint: false, idempotentHint: false),
    outputSchema: SessionSummary::SCHEMA,
)]
final readonly class LogSetTool
{
    use RefusesPolitely;

    public function __construct(
        private LogSet $logSet,
        private ClockInterface $clock,
    ) {
    }

    #[Schema(additionalProperties: false)]
    public function __invoke(
        #[Schema(description: 'Position of the planned line just performed, as shown by current_session. Omit it only for a set outside the plan.', minimum: 1)]
        ?int $position = null,
        #[Schema(description: 'Exercise id, required only when no position is given')]
        ?string $exercise = null,
        #[Schema(description: 'Repetitions performed, defaulting to what the line planned', minimum: 1, maximum: Limits::MAX_REPS)]
        ?int $reps = null,
        #[Schema(description: 'Seconds held, defaulting to what the line planned', minimum: 1, maximum: Limits::MAX_SECONDS)]
        ?int $seconds = null,
        #[Schema(description: 'Load in kilograms, defaulting to what the line planned. Required for a lift with no planned weight; bodyweight work needs none.', minimum: 0, maximum: Limits::MAX_WEIGHT)]
        ?float $weight = null,
    ): SessionSummary {
        return $this->refusing(fn (): SessionSummary => SessionSummary::of(($this->logSet)($position, $exercise, $reps, $seconds, $weight), $this->clock->now()));
    }
}
