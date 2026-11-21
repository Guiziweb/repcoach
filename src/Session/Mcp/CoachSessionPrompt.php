<?php

declare(strict_types=1);

namespace App\Session\Mcp;

use App\Workout\Mcp\WorkoutNameCompletion;
use Mcp\Capability\Attribute\CompletionProvider;
use Mcp\Capability\Attribute\McpPrompt;

#[McpPrompt(
    name: 'coach_session',
    title: 'Coach me through a session',
    description: 'Start a gym session and coach it set by set. Optionally name the saved workout to do.',
)]
final readonly class CoachSessionPrompt
{
    /**
     * @param string|null $workout name of the saved workout to do, as returned by list_workouts
     *
     * @return list<array{role: string, content: string}>
     */
    public function __invoke(#[CompletionProvider(provider: WorkoutNameCompletion::class)] ?string $workout = null): array
    {
        $opening = null === $workout || '' === trim($workout)
            ? 'Call list_workouts, suggest which saved workout to do and wait for my choice before starting it.'
            : \sprintf('Start the workout "%s" with start_session.', trim($workout));

        return [[
            'role' => 'user',
            'content' => \sprintf(
                'Coach me through a gym session. %s Then, each time I tell you what I did, record it with log_set and answer in one short sentence: what comes next on the plan and whether I should adjust the load. Use current_session when you need to know where we are. When I say I am done, call finish_session and sum up the volume. Take every number from the tool results, never from memory. Answer in my language.',
                $opening,
            ),
        ]];
    }
}
