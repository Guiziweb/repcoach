<?php

declare(strict_types=1);

namespace App\Session\Mcp;

use App\Session\Action\AddSet;
use App\Shared\Limits;
use App\Shared\Mcp\RefusesPolitely;
use Mcp\Capability\Attribute\McpTool;
use Mcp\Capability\Attribute\Schema;
use Mcp\Schema\ToolAnnotations;
use Psr\Clock\ClockInterface;

#[McpTool(
    name: 'add_set',
    title: 'Add a forgotten set',
    description: 'Add a set to a session, for the planned line at the given position. Reps, seconds and weight default to what that line planned.',
    annotations: new ToolAnnotations(readOnlyHint: false, destructiveHint: false, idempotentHint: false),
    outputSchema: SessionSummary::SCHEMA,
)]
final readonly class AddSetTool
{
    use RefusesPolitely;

    public function __construct(
        private AddSet $add,
        private ClockInterface $clock,
    ) {
    }

    public function __invoke(
        #[Schema(description: 'Id of the session, as shown by list_sessions', minimum: 1)]
        int $session,
        #[Schema(description: 'Position of the planned line the set belongs to, as shown in the session plan', minimum: 1)]
        int $position,
        #[Schema(description: 'Repetitions performed, defaulting to what the line planned', minimum: 1, maximum: Limits::MAX_REPS)]
        ?int $reps = null,
        #[Schema(description: 'Seconds held, defaulting to what the line planned', minimum: 1, maximum: Limits::MAX_SECONDS)]
        ?int $seconds = null,
        #[Schema(description: 'Load in kilograms, defaulting to what the line planned. Required for a lift with no planned weight; bodyweight work needs none.', minimum: 0, maximum: Limits::MAX_WEIGHT)]
        ?float $weight = null,
    ): SessionSummary {
        return $this->refusing(fn (): SessionSummary => SessionSummary::of(($this->add)($session, $position, $reps, $seconds, $weight), $this->clock->now()));
    }
}
