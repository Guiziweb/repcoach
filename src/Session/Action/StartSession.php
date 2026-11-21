<?php

declare(strict_types=1);

namespace App\Session\Action;

use App\Session\WorkoutSession;
use App\Session\WorkoutSessionRepository;
use App\Shared\Reason;
use App\Shared\Refused;
use App\Workout\WorkoutRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Clock\ClockInterface;

final readonly class StartSession
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private WorkoutSessionRepository $sessions,
        private WorkoutRepository $workouts,
        private ClockInterface $clock,
    ) {
    }

    public function __invoke(string $workout): WorkoutSession
    {
        if (null !== $this->sessions->open()) {
            throw new Refused('A session is already open.', Reason::SessionAlreadyOpen);
        }

        $session = new WorkoutSession($this->clock->now(), $this->workouts->get($workout));
        $this->entityManager->persist($session);
        $this->entityManager->flush();

        return $session;
    }
}
