<?php

declare(strict_types=1);

namespace App\Session\Mcp;

use App\Session\Action\PauseSession;
use App\Shared\Mcp\RefusesPolitely;
use Mcp\Capability\Attribute\McpTool;
use Mcp\Schema\ToolAnnotations;
use Psr\Clock\ClockInterface;

#[McpTool(
    name: 'pause_session',
    title: 'Pause the session',
    description: 'Stop the clock while the session stays open. Sets cannot be logged until it resumes.',
    annotations: new ToolAnnotations(readOnlyHint: false, destructiveHint: false, idempotentHint: true),
    outputSchema: SessionSummary::SCHEMA,
)]
final readonly class PauseSessionTool
{
    use RefusesPolitely;

    public function __construct(
        private PauseSession $pause,
        private ClockInterface $clock,
    ) {
    }

    public function __invoke(): SessionSummary
    {
        return $this->refusing(fn (): SessionSummary => SessionSummary::of(($this->pause)(), $this->clock->now()));
    }
}
