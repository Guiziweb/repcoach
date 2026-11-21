<?php

declare(strict_types=1);

namespace App\Workout\Action;

use App\Workout\Workout;
use App\Workout\WorkoutNames;
use App\Workout\WorkoutRepository;
use Doctrine\ORM\EntityManagerInterface;

final readonly class RenameWorkout
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private WorkoutRepository $workouts,
        private WorkoutNames $names,
    ) {
    }

    public function __invoke(string $workout, string $name): Workout
    {
        $plan = $this->workouts->get($workout);

        $plan->rename($this->names->free($name, $plan));
        $this->entityManager->flush();

        return $plan;
    }
}
