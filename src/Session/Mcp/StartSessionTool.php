<?php

declare(strict_types=1);

namespace App\Session\Mcp;

use App\Session\Action\StartSession;
use App\Shared\Mcp\RefusesPolitely;
use Mcp\Capability\Attribute\McpTool;
use Mcp\Capability\Attribute\Schema;
use Mcp\Schema\ToolAnnotations;
use Psr\Clock\ClockInterface;

#[McpTool(
    name: 'start_session',
    title: 'Start a workout session',
    description: 'Open a session for one of the saved workouts. Fails when a session is already open.',
    annotations: new ToolAnnotations(readOnlyHint: false, destructiveHint: false, idempotentHint: false),
    outputSchema: SessionSummary::SCHEMA,
)]
final readonly class StartSessionTool
{
    use RefusesPolitely;

    public function __construct(
        private StartSession $start,
        private ClockInterface $clock,
    ) {
    }

    #[Schema(additionalProperties: false)]
    public function __invoke(
        #[Schema(description: 'Name of a saved workout, as returned by list_workouts')]
        string $workout,
    ): SessionSummary {
        return $this->refusing(fn (): SessionSummary => SessionSummary::of(($this->start)($workout), $this->clock->now()));
    }
}
