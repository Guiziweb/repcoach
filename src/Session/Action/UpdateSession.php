<?php

declare(strict_types=1);

namespace App\Session\Action;

use App\Session\WorkoutSession;
use App\Session\WorkoutSessionRepository;
use App\Shared\Check;
use App\Shared\Minutes;
use App\Shared\Reason;
use App\Shared\Refused;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Clock\ClockInterface;
use Symfony\Component\Validator\Constraints\LessThanOrEqual;

final readonly class UpdateSession
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private WorkoutSessionRepository $sessions,
        private Check $check,
        private ClockInterface $clock,
    ) {
    }

    public function __invoke(int $session, ?DateTimeImmutable $startedAt, ?int $durationMinutes): WorkoutSession
    {
        if (null === $startedAt && null === $durationMinutes) {
            throw new Refused('Give a new start or a new duration, there is nothing to change otherwise.');
        }

        ($this->check)($durationMinutes, new Minutes());

        $found = $this->sessions->get($session);

        if ($found->getStatus()->isOpen()) {
            throw new Refused('That session is still open.', Reason::SessionStillOpenForCorrection);
        }

        $this->mustNotReachIntoTheFuture($found, $startedAt, $durationMinutes);

        $found->reschedule($startedAt, $durationMinutes);
        $this->entityManager->flush();

        return $found;
    }

    private function mustNotReachIntoTheFuture(WorkoutSession $session, ?DateTimeImmutable $startedAt, ?int $durationMinutes): void
    {
        $now = $this->clock->now();

        if (null !== $startedAt) {
            ($this->check)($startedAt, new LessThanOrEqual(value: $now, message: 'A session cannot have started in the future.'));
        }

        ($this->check)($session->endIfRescheduled($startedAt, $durationMinutes), new LessThanOrEqual(value: $now, message: 'A session cannot end in the future. Give an earlier start as well.'));
    }
}
