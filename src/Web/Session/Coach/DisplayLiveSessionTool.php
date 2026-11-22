<?php

declare(strict_types=1);

namespace App\Web\Session\Coach;

use App\Session\WorkoutSessionRepository;
use App\Web\Coach\Display\Destination;
use Symfony\AI\Agent\Toolbox\Attribute\AsTool;
use Symfony\AI\Platform\Tool\ToolEffectHints;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

#[AsTool('display_live_session', 'Bring the running or paused session onto the screen, with the sets done and the sets left.', effectHints: new ToolEffectHints(readOnlyHint: true))]
final readonly class DisplayLiveSessionTool
{
    public function __construct(
        private WorkoutSessionRepository $sessions,
        private UrlGeneratorInterface $urls,
        private Destination $destination,
    ) {
    }

    public function __invoke(): string
    {
        if (null === $this->sessions->open()) {
            return 'No session is open. Start one with start_session first.';
        }

        $this->destination->go($this->urls->generate('live_session'));

        return 'The running session is now on screen.';
    }
}
