<?php

declare(strict_types=1);

namespace App\Session\Mcp;

use App\Session\Action\AbortSession;
use App\Shared\Mcp\RefusesPolitely;
use Mcp\Capability\Attribute\McpTool;
use Mcp\Capability\Attribute\Schema;
use Mcp\Schema\ToolAnnotations;
use Psr\Clock\ClockInterface;

#[McpTool(
    name: 'abort_session',
    title: 'Abort the session',
    description: 'Close the open session without counting it as done.',
    annotations: new ToolAnnotations(readOnlyHint: false, destructiveHint: true, idempotentHint: false),
    outputSchema: SessionSummary::SCHEMA,
)]
final readonly class AbortSessionTool
{
    use RefusesPolitely;

    public function __construct(
        private AbortSession $abort,
        private ClockInterface $clock,
    ) {
    }

    #[Schema(additionalProperties: false)]
    public function __invoke(): SessionSummary
    {
        return $this->refusing(fn (): SessionSummary => SessionSummary::of(($this->abort)(), $this->clock->now()));
    }
}
