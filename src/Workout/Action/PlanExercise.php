<?php

declare(strict_types=1);

namespace App\Workout\Action;

use App\Exercise\ExerciseRepository;
use App\Shared\Check;
use App\Shared\PlannedEffort;
use App\Workout\Workout;
use App\Workout\WorkoutExercise;
use App\Workout\WorkoutRepository;
use Doctrine\ORM\EntityManagerInterface;

final readonly class PlanExercise
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private WorkoutRepository $workouts,
        private ExerciseRepository $exercises,
        private Check $check,
    ) {
    }

    public function __invoke(string $workout, string $exercise, int $sets, ?int $reps, ?int $seconds, ?float $weight): Workout
    {
        ($this->check)(new PlannedEffort($sets, $reps, $seconds, $weight));

        $plan = $this->workouts->get($workout);
        $found = $this->exercises->get($exercise);

        new WorkoutExercise($plan, $found, $plan->nextPosition(), $sets, $reps, $seconds, $weight);
        $this->entityManager->flush();

        return $plan;
    }
}
