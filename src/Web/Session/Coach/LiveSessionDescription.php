<?php

declare(strict_types=1);

namespace App\Web\Session\Coach;

use App\Web\Coach\Screen\DescribesScreen;
use App\Web\Coach\Screen\EffortInWords;
use App\Web\Session\SessionPages;
use App\Web\Session\View\LiveLine;
use App\Web\Session\View\LiveSessionView;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;

#[AsTaggedItem('live_session')]
final readonly class LiveSessionDescription implements DescribesScreen
{
    public function __construct(
        private SessionPages $pages,
    ) {
    }

    public function describe(array $parameters): string
    {
        return self::of($this->pages->live());
    }

    public static function of(?LiveSessionView $view): string
    {
        if (null === $view) {
            return 'No session is open. The screen invites the user to start one.';
        }

        $session = $view->session;
        $lines = array_map(
            static fn (LiveLine $line): string => \sprintf(
                'Line %d, %s, planned %d sets of %s: %s, %d left',
                $line->planned->position,
                $line->planned->exercise->name,
                $line->planned->sets,
                EffortInWords::of($line->planned->reps, $line->planned->seconds, $line->planned->weight),
                SetsInWords::of($line->done),
                $line->remaining(),
            ),
            $view->lines,
        );

        foreach ($view->extras as $exercise => $sets) {
            $lines[] = \sprintf('Off plan, %s: %s', $exercise, SetsInWords::of($sets));
        }

        return \sprintf(
            "Session %d, workout \"%s\", %s, started %s, %d minutes so far:\n%s",
            $session->getId(),
            $session->workoutName,
            $session->getStatus()->value,
            $session->startedAt->format('H:i'),
            $view->duration->minutes(),
            implode("\n", $lines),
        );
    }
}
