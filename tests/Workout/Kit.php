<?php

declare(strict_types=1);

namespace App\Tests\Workout;

use App\Exercise\ExerciseRepository;
use App\Tests\Checker;
use App\Workout\Action\ChangeExercise;
use App\Workout\Action\CreateWorkout;
use App\Workout\Action\MoveExercise;
use App\Workout\Action\PlanExercise;
use App\Workout\Action\RemoveExercise;
use App\Workout\Action\RenameWorkout;
use App\Workout\Mcp\ChangeExerciseTool;
use App\Workout\Mcp\CreateWorkoutTool;
use App\Workout\Mcp\ListWorkoutsTool;
use App\Workout\Mcp\MoveExerciseTool;
use App\Workout\Mcp\PlanExerciseTool;
use App\Workout\Mcp\RemoveExerciseTool;
use App\Workout\Mcp\RenameWorkoutTool;
use App\Workout\Mcp\ShowWorkoutTool;
use App\Workout\WorkoutNames;
use App\Workout\WorkoutRepository;
use Doctrine\ORM\EntityManagerInterface;

final readonly class Kit
{
    public ListWorkoutsTool $listWorkouts;

    public ShowWorkoutTool $showWorkout;

    public CreateWorkoutTool $createWorkout;

    public PlanExerciseTool $planExercise;

    public ChangeExerciseTool $changeExercise;

    public RemoveExerciseTool $removeExercise;

    public MoveExerciseTool $moveExercise;

    public RenameWorkoutTool $renameWorkout;

    public function __construct(
        EntityManagerInterface $entityManager,
        WorkoutRepository $workouts,
        ExerciseRepository $exercises,
    ) {
        $check = Checker::make();
        $names = new WorkoutNames($check, $workouts);

        $this->listWorkouts = new ListWorkoutsTool($workouts);
        $this->showWorkout = new ShowWorkoutTool($workouts);
        $this->createWorkout = new CreateWorkoutTool(new CreateWorkout($entityManager, $names));
        $this->planExercise = new PlanExerciseTool(new PlanExercise($entityManager, $workouts, $exercises, $check));
        $this->changeExercise = new ChangeExerciseTool(new ChangeExercise($entityManager, $workouts, $check));
        $this->removeExercise = new RemoveExerciseTool(new RemoveExercise($entityManager, $workouts));
        $this->moveExercise = new MoveExerciseTool(new MoveExercise($entityManager, $workouts));
        $this->renameWorkout = new RenameWorkoutTool(new RenameWorkout($entityManager, $workouts, $names));
    }
}
