<?php

declare(strict_types=1);

namespace App\Session\Mcp;

use App\Session\Action\ChangeSet;
use App\Shared\Limits;
use App\Shared\Mcp\RefusesPolitely;
use Mcp\Capability\Attribute\McpTool;
use Mcp\Capability\Attribute\Schema;
use Mcp\Schema\ToolAnnotations;
use Psr\Clock\ClockInterface;

#[McpTool(
    name: 'change_set',
    title: 'Correct a set',
    description: 'Correct one performed set in place. Only what you give changes: the weight stays when omitted, and giving reps or seconds replaces the effort of the set. Give either reps or seconds, not both.',
    annotations: new ToolAnnotations(readOnlyHint: false, destructiveHint: false, idempotentHint: true),
    outputSchema: SessionSummary::SCHEMA,
)]
final readonly class ChangeSetTool
{
    use RefusesPolitely;

    public function __construct(
        private ChangeSet $change,
        private ClockInterface $clock,
    ) {
    }

    public function __invoke(
        #[Schema(description: 'Id of the set, as shown by show_session or current_session', minimum: 1)]
        int $set,
        #[Schema(description: 'Repetitions performed. Omit both reps and seconds to keep the effort as it is', minimum: 1, maximum: Limits::MAX_REPS)]
        ?int $reps = null,
        #[Schema(description: 'Seconds held, for planks and stretches. Omit both reps and seconds to keep the effort as it is', minimum: 1, maximum: Limits::MAX_SECONDS)]
        ?int $seconds = null,
        #[Schema(description: 'Load in kilograms. Omit it to keep the current load. Give 0 to remove the load from a bodyweight exercise', minimum: 0, maximum: Limits::MAX_WEIGHT)]
        ?float $weight = null,
    ): SessionSummary {
        return $this->refusing(fn (): SessionSummary => SessionSummary::of(($this->change)($set, $reps, $seconds, $weight), $this->clock->now()));
    }
}
