<?php

declare(strict_types=1);

namespace App\Workout\Action;

use App\Workout\Workout;
use App\Workout\WorkoutNames;
use Doctrine\ORM\EntityManagerInterface;

final readonly class CreateWorkout
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private WorkoutNames $names,
    ) {
    }

    public function __invoke(string $name): Workout
    {
        $workout = new Workout($this->names->free($name));
        $this->entityManager->persist($workout);
        $this->entityManager->flush();

        return $workout;
    }
}
