<?php

declare(strict_types=1);

namespace App\Web\Session;

use App\Session\ClosedStatus;
use App\Session\SessionStatus;
use App\Session\SessionTally;
use App\Session\WorkoutSession;
use App\Session\WorkoutSessionRepository;
use App\Shared\NotFound;
use App\Web\Session\View\Duration;
use App\Web\Session\View\LiveSessionView;
use App\Web\Session\View\SessionRow;
use App\Web\Session\View\SessionView;
use Pagerfanta\Adapter\CallbackAdapter;
use Pagerfanta\Pagerfanta;
use Psr\Clock\ClockInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class SessionPages
{
    public function __construct(
        private WorkoutSessionRepository $sessions,
        private ClockInterface $clock,
        #[Autowire(param: 'app.sessions.per_page')]
        private int $perPage,
    ) {
    }

    /**
     * @return Pagerfanta<SessionRow>
     */
    public function page(int $page): Pagerfanta
    {
        $adapter = new CallbackAdapter(
            fn (): int => $this->sessions->countClosed(ClosedStatus::Finished, null, null, null),
            fn (int $offset, int $length): array => array_map(
                fn (SessionTally $tally): SessionRow => new SessionRow($tally, $this->duration($tally->session)),
                $this->sessions->closed(ClosedStatus::Finished, null, null, null, $length, $offset),
            ),
        );

        return (new Pagerfanta($adapter))
            ->setMaxPerPage($this->perPage)
            ->setNormalizeOutOfRangePages(true)
            ->setCurrentPage(max(1, $page));
    }

    public function isLive(int $id): bool
    {
        return $id === $this->sessions->open()?->getId();
    }

    public function live(): ?LiveSessionView
    {
        $session = $this->sessions->open();

        return null === $session ? null : LiveSessionView::of($session, $this->duration($session));
    }

    public function session(int $id): ?SessionView
    {
        try {
            $session = $this->sessions->get($id);
        } catch (NotFound) {
            return null;
        }

        if (SessionStatus::Finished !== $session->getStatus()) {
            return null;
        }

        return SessionView::of(
            $session,
            $this->duration($session),
            $this->sessions->closedBefore($session, ClosedStatus::Finished),
            $this->sessions->closedAfter($session, ClosedStatus::Finished),
            intdiv($this->sessions->countClosedAfter($session, ClosedStatus::Finished), $this->perPage) + 1,
        );
    }

    private function duration(WorkoutSession $session): Duration
    {
        return new Duration($session->durationInSeconds($this->clock->now()));
    }
}
