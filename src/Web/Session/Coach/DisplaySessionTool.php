<?php

declare(strict_types=1);

namespace App\Web\Session\Coach;

use App\Session\SessionStatus;
use App\Session\WorkoutSessionRepository;
use App\Shared\NotFound;
use App\Web\Coach\Display\Destination;
use Symfony\AI\Agent\Toolbox\Attribute\AsTool;
use Symfony\AI\Platform\Tool\ToolEffectHints;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

#[AsTool('display_session', 'Bring a finished session onto the screen. Use it whenever the user wants to see a session, instead of describing it in words.', effectHints: new ToolEffectHints(readOnlyHint: true))]
final readonly class DisplaySessionTool
{
    public function __construct(
        private WorkoutSessionRepository $sessions,
        private UrlGeneratorInterface $urls,
        private Destination $destination,
    ) {
    }

    /**
     * @param int $id id of the session, as returned by list_sessions
     */
    public function __invoke(int $id): string
    {
        try {
            $session = $this->sessions->get($id);
        } catch (NotFound) {
            return \sprintf('There is no session %d. Use list_sessions to find it.', $id);
        }

        if ($session->getStatus()->isOpen()) {
            return \sprintf('Session %d is still open. Show it with display_live_session.', $id);
        }

        if (SessionStatus::Finished !== $session->getStatus()) {
            return \sprintf('Session %d was aborted, only finished sessions can be shown.', $id);
        }

        $this->destination->go($this->urls->generate('session', ['id' => $id]));

        return \sprintf('Session %d is now on screen.', $id);
    }
}
