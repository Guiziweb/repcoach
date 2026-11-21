<?php

declare(strict_types=1);

namespace App\Tests\Workout;

use App\Tests\Checker;
use App\Workout\Action\CreateWorkout;
use App\Workout\Mcp\CreateWorkoutTool;
use App\Workout\WorkoutNames;
use App\Workout\WorkoutRepository;
use Doctrine\ORM\EntityManagerInterface;

final readonly class Kit
{
    public CreateWorkoutTool $createWorkout;

    public function __construct(
        EntityManagerInterface $entityManager,
        WorkoutRepository $workouts,
    ) {
        $names = new WorkoutNames(Checker::make(), $workouts);

        $this->createWorkout = new CreateWorkoutTool(new CreateWorkout($entityManager, $names));
    }
}
