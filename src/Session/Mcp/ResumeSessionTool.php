<?php

declare(strict_types=1);

namespace App\Session\Mcp;

use App\Session\Action\ResumeSession;
use App\Shared\Mcp\RefusesPolitely;
use Mcp\Capability\Attribute\McpTool;
use Mcp\Capability\Attribute\Schema;
use Mcp\Schema\ToolAnnotations;
use Psr\Clock\ClockInterface;

#[McpTool(
    name: 'resume_session',
    title: 'Resume the session',
    description: 'Restart the clock on a paused session.',
    annotations: new ToolAnnotations(readOnlyHint: false, destructiveHint: false, idempotentHint: true),
    outputSchema: SessionSummary::SCHEMA,
)]
final readonly class ResumeSessionTool
{
    use RefusesPolitely;

    public function __construct(
        private ResumeSession $resume,
        private ClockInterface $clock,
    ) {
    }

    #[Schema(additionalProperties: false)]
    public function __invoke(): SessionSummary
    {
        return $this->refusing(fn (): SessionSummary => SessionSummary::of(($this->resume)(), $this->clock->now()));
    }
}
