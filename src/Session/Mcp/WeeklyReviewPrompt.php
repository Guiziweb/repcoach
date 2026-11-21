<?php

declare(strict_types=1);

namespace App\Session\Mcp;

use Mcp\Capability\Attribute\McpPrompt;
use Psr\Clock\ClockInterface;

#[McpPrompt(
    name: 'weekly_review',
    title: 'Review my last week',
    description: 'Look back at the sessions of the last seven days and say what progressed, what stalled and what to do next.',
)]
final readonly class WeeklyReviewPrompt
{
    public function __construct(
        private ClockInterface $clock,
    ) {
    }

    /**
     * @return list<array{role: string, content: string}>
     */
    public function __invoke(): array
    {
        $today = $this->clock->now();

        return [[
            'role' => 'user',
            'content' => \sprintf(
                'Review my training from %s to %s. Call list_sessions with those two dates, then show_session for the sessions worth a closer look. Tell me how many sessions I did, the volume of each, what progressed or stalled compared with the previous ones, and one concrete thing to do next week. Take every number from the tool results. Answer in my language.',
                $today->modify('-6 days')->format('Y-m-d'),
                $today->format('Y-m-d'),
            ),
        ]];
    }
}
