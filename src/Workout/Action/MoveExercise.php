<?php

declare(strict_types=1);

namespace App\Workout\Action;

use App\Workout\Workout;
use App\Workout\WorkoutRepository;
use Doctrine\ORM\EntityManagerInterface;

final readonly class MoveExercise
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private WorkoutRepository $workouts,
    ) {
    }

    public function __invoke(string $workout, int $position, int $to): Workout
    {
        $plan = $this->workouts->get($workout);

        $plan->move($plan->line($position), $to);
        $this->entityManager->flush();

        return $plan;
    }
}
