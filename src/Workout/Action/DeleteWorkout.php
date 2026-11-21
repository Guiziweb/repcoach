<?php

declare(strict_types=1);

namespace App\Workout\Action;

use App\Session\WorkoutSessionRepository;
use App\Workout\WorkoutRepository;
use Doctrine\ORM\EntityManagerInterface;

final readonly class DeleteWorkout
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private WorkoutRepository $workouts,
        private WorkoutSessionRepository $sessions,
    ) {
    }

    public function __invoke(string $workout): void
    {
        $plan = $this->workouts->get($workout);

        foreach ($this->sessions->of($plan) as $session) {
            $session->forget();
        }

        $this->entityManager->remove($plan);
        $this->entityManager->flush();
    }
}
