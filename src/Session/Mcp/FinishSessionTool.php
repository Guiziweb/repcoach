<?php

declare(strict_types=1);

namespace App\Session\Mcp;

use App\Session\Action\FinishSession;
use App\Shared\Limits;
use App\Shared\Mcp\RefusesPolitely;
use Mcp\Capability\Attribute\McpTool;
use Mcp\Capability\Attribute\Schema;
use Mcp\Schema\ToolAnnotations;
use Psr\Clock\ClockInterface;

#[McpTool(
    name: 'finish_session',
    title: 'Finish the session',
    description: 'Close the open session and return its totals. When the sets were entered after the workout, ask the user how long it lasted and pass durationMinutes; otherwise the duration is the time since the session was opened. If the duration or the start turns out wrong afterwards, correct it with update_session.',
    annotations: new ToolAnnotations(readOnlyHint: false, destructiveHint: false, idempotentHint: false),
    outputSchema: SessionSummary::SCHEMA,
)]
final readonly class FinishSessionTool
{
    use RefusesPolitely;

    public function __construct(
        private FinishSession $finish,
        private ClockInterface $clock,
    ) {
    }

    public function __invoke(
        #[Schema(description: 'How long the workout really lasted, in minutes. Give it when the sets were entered after the workout rather than during it.', minimum: 1, maximum: Limits::MAX_MINUTES)]
        ?int $durationMinutes = null,
    ): SessionSummary {
        return $this->refusing(fn (): SessionSummary => SessionSummary::of(($this->finish)($durationMinutes), $this->clock->now()));
    }
}
