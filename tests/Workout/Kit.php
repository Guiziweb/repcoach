<?php

declare(strict_types=1);

namespace App\Tests\Workout;

use App\Tests\Checker;
use App\Workout\Action\CreateWorkout;
use App\Workout\Mcp\CreateWorkoutTool;
use App\Workout\Mcp\ListWorkoutsTool;
use App\Workout\Mcp\ShowWorkoutTool;
use App\Workout\WorkoutNames;
use App\Workout\WorkoutRepository;
use Doctrine\ORM\EntityManagerInterface;

final readonly class Kit
{
    public ListWorkoutsTool $listWorkouts;

    public ShowWorkoutTool $showWorkout;

    public CreateWorkoutTool $createWorkout;

    public function __construct(
        EntityManagerInterface $entityManager,
        WorkoutRepository $workouts,
    ) {
        $names = new WorkoutNames(Checker::make(), $workouts);

        $this->listWorkouts = new ListWorkoutsTool($workouts);
        $this->showWorkout = new ShowWorkoutTool($workouts);
        $this->createWorkout = new CreateWorkoutTool(new CreateWorkout($entityManager, $names));
    }
}
