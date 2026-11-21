<?php

declare(strict_types=1);

namespace App\Workout\Action;

use App\Shared\Check;
use App\Shared\PlannedEffort;
use App\Workout\Workout;
use App\Workout\WorkoutRepository;
use Doctrine\ORM\EntityManagerInterface;

final readonly class ChangeExercise
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private WorkoutRepository $workouts,
        private Check $check,
    ) {
    }

    public function __invoke(string $workout, int $position, int $sets, ?int $reps, ?int $seconds, ?float $weight): Workout
    {
        ($this->check)(new PlannedEffort($sets, $reps, $seconds, $weight));

        $plan = $this->workouts->get($workout);

        $plan->line($position)->change($sets, $reps, $seconds, $weight);
        $this->entityManager->flush();

        return $plan;
    }
}
