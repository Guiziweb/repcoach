<?php

declare(strict_types=1);

namespace App\Session\Action;

use App\Session\WorkoutSession;
use App\Session\WorkoutSessionRepository;
use App\Shared\Check;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Clock\ClockInterface;

final readonly class AddSet
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private WorkoutSessionRepository $sessions,
        private ClockInterface $clock,
        private Check $check,
    ) {
    }

    public function __invoke(int $session, int $position, ?int $reps, ?int $seconds, ?float $weight): WorkoutSession
    {
        $found = $this->sessions->get($session);
        $line = $found->line($position);
        $effort = $line->effortFor($reps, $seconds, $weight);
        ($this->check)($effort);
        $line->perform($effort, $found->getEndedAt() ?? $this->clock->now());
        $this->entityManager->flush();

        return $found;
    }
}
