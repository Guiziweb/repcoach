<?php

declare(strict_types=1);

namespace App\Session\Mcp;

use App\Session\ClosedStatus;
use App\Session\SessionTally;
use App\Session\WorkoutSessionRepository;
use App\Shared\Check;
use App\Shared\Limits;
use App\Shared\Mcp\RefusesPolitely;
use DateTimeImmutable;
use Mcp\Capability\Attribute\McpTool;
use Mcp\Capability\Attribute\Schema;
use Mcp\Schema\ToolAnnotations;
use Psr\Clock\ClockInterface;
use Symfony\Component\Validator\Constraints\Date;
use Symfony\Component\Validator\Constraints\NotBlank;

#[McpTool(
    name: 'list_sessions',
    title: 'List past sessions',
    description: 'Past sessions, most recent first, one line each: id, workout, start, duration, total volume and number of sets. Filter by workout name or by day to answer "what did I do last Monday". Pass an id as sessionId to show_session to see the sets. "matching" counts every session that fits the filters, even beyond the limit.',
    annotations: new ToolAnnotations(readOnlyHint: true, idempotentHint: true, openWorldHint: false),
    outputSchema: ['type' => 'object', 'required' => ['matching', 'sessions'], 'properties' => ['matching' => ['type' => 'integer'], 'sessions' => ['type' => 'array', 'items' => SessionOverview::SCHEMA]]],
)]
final readonly class ListSessionsTool
{
    use RefusesPolitely;

    public function __construct(
        private WorkoutSessionRepository $sessions,
        private Check $check,
        private ClockInterface $clock,
    ) {
    }

    /**
     * @return array{matching: int, sessions: list<SessionOverview>}
     */
    #[Schema(additionalProperties: false)]
    public function __invoke(
        #[Schema(description: 'How many sessions to return', minimum: 1, maximum: Limits::MAX_SESSIONS)]
        int $limit = 10,
        #[Schema(description: 'Only the sessions of the workout with this name, as returned by list_workouts')]
        ?string $workout = null,
        #[Schema(description: 'First day to include, as YYYY-MM-DD')]
        ?string $from = null,
        #[Schema(description: 'Last day to include, as YYYY-MM-DD')]
        ?string $to = null,
        #[Schema(description: 'finished for completed sessions, aborted for the ones stopped early')]
        ClosedStatus $status = ClosedStatus::Finished,
    ): array {
        return $this->refusing(function () use ($limit, $workout, $from, $to, $status): array {
            $start = $this->day($from);
            $until = $this->day($to)?->modify('+1 day');
            $now = $this->clock->now();

            return [
                'matching' => $this->sessions->countClosed($status, $workout, $start, $until),
                'sessions' => array_map(
                    static fn (SessionTally $tally): SessionOverview => SessionOverview::of($tally, $now),
                    $this->sessions->closed($status, $workout, $start, $until, $limit),
                ),
            ];
        });
    }

    private function day(?string $text): ?DateTimeImmutable
    {
        if (null === $text) {
            return null;
        }

        $message = 'Use YYYY-MM-DD, for example 2026-11-15.';
        ($this->check)($text, [new NotBlank(message: $message), new Date(message: '{{ value }} is not a day. '.$message)]);

        return (new DateTimeImmutable($text))->setTime(0, 0);
    }
}
