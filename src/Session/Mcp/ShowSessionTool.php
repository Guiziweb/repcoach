<?php

declare(strict_types=1);

namespace App\Session\Mcp;

use App\Session\WorkoutSessionRepository;
use App\Shared\Mcp\RefusesPolitely;
use Mcp\Capability\Attribute\McpTool;
use Mcp\Capability\Attribute\Schema;
use Mcp\Schema\ToolAnnotations;
use Psr\Clock\ClockInterface;

#[McpTool(
    name: 'show_session',
    title: 'Show one past session',
    description: 'One session in full: every set performed with its id, the exercise, repetitions or seconds and weight, and the planned lines with how many sets were done. Use it to find the id of a set, then pass it as setId to change_set or remove_set.',
    annotations: new ToolAnnotations(readOnlyHint: true, idempotentHint: true, openWorldHint: false),
    outputSchema: SessionSummary::SCHEMA,
)]
final readonly class ShowSessionTool
{
    use RefusesPolitely;

    public function __construct(
        private WorkoutSessionRepository $sessions,
        private ClockInterface $clock,
    ) {
    }

    #[Schema(additionalProperties: false)]
    public function __invoke(
        #[Schema(description: 'Id of the session, as shown by list_sessions', minimum: 1)]
        int $sessionId,
    ): SessionSummary {
        return $this->refusing(fn (): SessionSummary => SessionSummary::of($this->sessions->get($sessionId), $this->clock->now()));
    }
}
