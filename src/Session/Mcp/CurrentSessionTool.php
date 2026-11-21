<?php

declare(strict_types=1);

namespace App\Session\Mcp;

use App\Session\WorkoutSessionRepository;
use Mcp\Capability\Attribute\McpTool;
use Mcp\Schema\ToolAnnotations;
use Psr\Clock\ClockInterface;

#[McpTool(
    name: 'current_session',
    title: 'Show the running session',
    description: 'Everything logged so far in the open session. Returns status "none" when no session is running, which is not an error.',
    annotations: new ToolAnnotations(readOnlyHint: true, idempotentHint: true, openWorldHint: false),
    outputSchema: SessionSummary::SCHEMA,
)]
final readonly class CurrentSessionTool
{
    public function __construct(
        private WorkoutSessionRepository $sessions,
        private ClockInterface $clock,
    ) {
    }

    public function __invoke(): SessionSummary
    {
        $session = $this->sessions->open();

        return null === $session ? SessionSummary::none() : SessionSummary::of($session, $this->clock->now());
    }
}
