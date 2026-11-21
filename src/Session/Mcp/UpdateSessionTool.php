<?php

declare(strict_types=1);

namespace App\Session\Mcp;

use App\Session\Action\UpdateSession;
use App\Shared\Check;
use App\Shared\Limits;
use App\Shared\Mcp\RefusesPolitely;
use DateTimeImmutable;
use Mcp\Capability\Attribute\McpTool;
use Mcp\Capability\Attribute\Schema;
use Mcp\Schema\ToolAnnotations;
use Psr\Clock\ClockInterface;
use Symfony\Component\Validator\Constraints\DateTime;
use Symfony\Component\Validator\Constraints\NotBlank;

#[McpTool(
    name: 'update_session',
    title: 'Correct a past session',
    description: 'Change when a finished or aborted session started, or how long it lasted. A session still open is closed with finish_session instead. The sets are not touched. Give the start with its timezone offset, for example 2026-11-15T09:38:00+01:00. Moving the start alone keeps the duration.',
    annotations: new ToolAnnotations(readOnlyHint: false, destructiveHint: false, idempotentHint: true),
    outputSchema: SessionSummary::SCHEMA,
)]
final readonly class UpdateSessionTool
{
    use RefusesPolitely;

    public function __construct(
        private UpdateSession $update,
        private Check $check,
        private ClockInterface $clock,
    ) {
    }

    #[Schema(additionalProperties: false)]
    public function __invoke(
        #[Schema(description: 'Id of the session, as shown by list_sessions', minimum: 1)]
        int $sessionId,
        #[Schema(description: 'When it started, as an ISO 8601 date and time with its timezone offset, in the past, for example 2026-11-15T09:38:00+01:00')]
        ?string $startedAt = null,
        #[Schema(description: 'How long it lasted, in minutes', minimum: 1, maximum: Limits::MAX_MINUTES)]
        ?int $durationMinutes = null,
    ): SessionSummary {
        return $this->refusing(fn (): SessionSummary => SessionSummary::of(
            ($this->update)($sessionId, null === $startedAt ? null : $this->moment($startedAt), $durationMinutes),
            $this->clock->now(),
        ));
    }

    private function moment(string $text): DateTimeImmutable
    {
        $message = 'Use ISO 8601 with its offset, for example 2026-11-15T09:38:00+01:00.';
        ($this->check)($text, [new NotBlank(message: $message), new DateTime(format: \DATE_ATOM, message: '{{ value }} is not a date. '.$message)]);

        return new DateTimeImmutable($text);
    }
}
