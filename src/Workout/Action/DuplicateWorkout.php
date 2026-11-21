<?php

declare(strict_types=1);

namespace App\Workout\Action;

use App\Workout\Workout;
use App\Workout\WorkoutExercise;
use App\Workout\WorkoutNames;
use App\Workout\WorkoutRepository;
use Doctrine\ORM\EntityManagerInterface;

final readonly class DuplicateWorkout
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private WorkoutRepository $workouts,
        private WorkoutNames $names,
    ) {
    }

    public function __invoke(string $workout, string $name): Workout
    {
        $source = $this->workouts->get($workout);
        $copy = new Workout($this->names->free($name));

        foreach ($source->getExercises() as $planned) {
            new WorkoutExercise($copy, $planned->exercise, $copy->nextPosition(), $planned->sets, $planned->reps, $planned->seconds, $planned->weight);
        }

        $this->entityManager->persist($copy);
        $this->entityManager->flush();

        return $copy;
    }
}
