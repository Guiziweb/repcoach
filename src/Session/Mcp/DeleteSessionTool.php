<?php

declare(strict_types=1);

namespace App\Session\Mcp;

use App\Session\Action\DeleteSession;
use App\Shared\Mcp\RefusesPolitely;
use Mcp\Capability\Attribute\McpTool;
use Mcp\Capability\Attribute\Schema;
use Mcp\Schema\ToolAnnotations;

#[McpTool(
    name: 'delete_session',
    title: 'Delete a past session',
    description: 'Delete a finished or aborted session with all its sets, for instance an empty one started by mistake. It cannot be undone.',
    annotations: new ToolAnnotations(readOnlyHint: false, destructiveHint: true, idempotentHint: false),
    outputSchema: ['type' => 'object', 'required' => ['deleted'], 'properties' => ['deleted' => ['type' => 'integer']]],
)]
final readonly class DeleteSessionTool
{
    use RefusesPolitely;

    public function __construct(
        private DeleteSession $delete,
    ) {
    }

    /**
     * @return array{deleted: int}
     */
    #[Schema(additionalProperties: false)]
    public function __invoke(
        #[Schema(description: 'Id of the session, as shown by list_sessions', minimum: 1)]
        int $sessionId,
    ): array {
        return $this->refusing(function () use ($sessionId): array {
            ($this->delete)($sessionId);

            return ['deleted' => $sessionId];
        });
    }
}
