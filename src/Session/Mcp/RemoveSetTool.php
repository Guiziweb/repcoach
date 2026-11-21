<?php

declare(strict_types=1);

namespace App\Session\Mcp;

use App\Session\Action\RemoveSet;
use App\Shared\Mcp\RefusesPolitely;
use Mcp\Capability\Attribute\McpTool;
use Mcp\Capability\Attribute\Schema;
use Mcp\Schema\ToolAnnotations;
use Psr\Clock\ClockInterface;

#[McpTool(
    name: 'remove_set',
    title: 'Remove a set',
    description: 'Delete one performed set that was entered by mistake.',
    annotations: new ToolAnnotations(readOnlyHint: false, destructiveHint: true, idempotentHint: false),
    outputSchema: SessionSummary::SCHEMA,
)]
final readonly class RemoveSetTool
{
    use RefusesPolitely;

    public function __construct(
        private RemoveSet $remove,
        private ClockInterface $clock,
    ) {
    }

    #[Schema(additionalProperties: false)]
    public function __invoke(
        #[Schema(description: 'Id of the set, as shown by show_session or current_session', minimum: 1)]
        int $setId,
    ): SessionSummary {
        return $this->refusing(fn (): SessionSummary => SessionSummary::of(($this->remove)($setId), $this->clock->now()));
    }
}
