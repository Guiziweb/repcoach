<?php

declare(strict_types=1);

namespace App\Web\Session\Coach;

use App\Web\Coach\Display\Destination;
use Symfony\AI\Agent\Toolbox\Attribute\AsTool;
use Symfony\AI\Platform\Tool\ToolEffectHints;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

#[AsTool('display_sessions', 'Bring the list of finished sessions onto the screen, most recent first.', effectHints: new ToolEffectHints(readOnlyHint: true))]
final readonly class DisplaySessionsTool
{
    public function __construct(
        private UrlGeneratorInterface $urls,
        private Destination $destination,
    ) {
    }

    /**
     * @param int $page page of the list, 1 being the most recent sessions
     */
    public function __invoke(int $page = 1): string
    {
        $this->destination->go($this->urls->generate('sessions', ['page' => max(1, $page)]));

        return 'The list of sessions is now on screen.';
    }
}
