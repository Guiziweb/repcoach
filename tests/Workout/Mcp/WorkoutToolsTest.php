<?php

declare(strict_types=1);

namespace App\Tests\Workout\Mcp;

use App\Exercise\ExerciseRepository;
use App\Session\WorkoutSessionRepository;
use App\Shared\Limits;
use App\Tests\ExerciseFixture;
use App\Tests\Workout\Kit;
use App\Workout\Mcp\PlannedExerciseView;
use App\Workout\Mcp\WorkoutSummary;
use App\Workout\WorkoutRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Mcp\Exception\ToolCallException;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class WorkoutToolsTest extends KernelTestCase
{
    private Kit $kit;

    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        self::bootKernel();

        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        self::assertInstanceOf(EntityManagerInterface::class, $entityManager);
        $this->entityManager = $entityManager;

        $metadata = $entityManager->getMetadataFactory()->getAllMetadata();
        $schema = new SchemaTool($entityManager);
        $schema->dropSchema($metadata);
        $schema->createSchema($metadata);

        $entityManager->persist(ExerciseFixture::exercise());
        $entityManager->persist(ExerciseFixture::exercise(id: 'plank', name: 'Plank', bodyweight: true));
        $entityManager->flush();

        $workouts = self::getContainer()->get(WorkoutRepository::class);
        self::assertInstanceOf(WorkoutRepository::class, $workouts);

        $sessions = self::getContainer()->get(WorkoutSessionRepository::class);
        self::assertInstanceOf(WorkoutSessionRepository::class, $sessions);

        $exercises = self::getContainer()->get(ExerciseRepository::class);
        self::assertInstanceOf(ExerciseRepository::class, $exercises);

        $this->kit = new Kit($entityManager, $workouts, $sessions, $exercises);
    }

    public function testItCreatesAnEmptyWorkout(): void
    {
        $workout = ($this->kit->createWorkout)('Pecs');

        self::assertSame('Pecs', $workout->name);
        self::assertSame([], $workout->exercises);
    }

    public function testItRefusesTwoWorkoutsWithTheSameName(): void
    {
        ($this->kit->createWorkout)('Pecs');

        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessageIsOrContains('already exists');

        ($this->kit->createWorkout)('Pecs');
    }

    public function testItRefusesANameThatOnlyDiffersByCase(): void
    {
        ($this->kit->createWorkout)('Pecs');

        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessageIsOrContains('already exists');

        ($this->kit->createWorkout)('pecs');
    }

    public function testItRefusesABlankWorkoutName(): void
    {
        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessageIsOrContains('needs a name');

        ($this->kit->createWorkout)('  ');
    }

    public function testItRefusesATooLongWorkoutName(): void
    {
        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessageIsOrContains('at most');

        ($this->kit->createWorkout)(str_repeat('a', Limits::MAX_NAME + 1));
    }

    public function testItListsWhatCanBeStarted(): void
    {
        ($this->kit->createWorkout)('Dos');
        ($this->kit->createWorkout)('Pecs');

        $listed = ($this->kit->listWorkouts)()['workouts'];

        self::assertSame(['Dos', 'Pecs'], array_map(static fn (WorkoutSummary $w): string => $w->name, $listed));
    }

    public function testItFindsAWorkoutWhateverTheCaseOrSpacing(): void
    {
        ($this->kit->createWorkout)('Pecs');

        self::assertSame('Pecs', ($this->kit->showWorkout)('pecs')->name);
        self::assertSame('Pecs', ($this->kit->showWorkout)('  PECS ')->name);
    }

    public function testItFindsAnAccentedWorkoutWhateverTheCase(): void
    {
        ($this->kit->createWorkout)('Épaules');

        self::assertSame('Épaules', ($this->kit->showWorkout)('épaules')->name);
    }

    public function testItKeepsAWorkoutNameWithoutTheSpacesAroundIt(): void
    {
        $workout = ($this->kit->createWorkout)('  Pecs ');

        self::assertSame('Pecs', $workout->name);
        self::assertSame('Pecs', ($this->kit->showWorkout)('Pecs')->name);
    }

    public function testItAppendsExercisesInOrder(): void
    {
        ($this->kit->createWorkout)('Pecs');
        ($this->kit->planExercise)('Pecs', 'bench-press', sets: 4, reps: 8, weight: 60.0);
        $workout = ($this->kit->planExercise)('Pecs', 'plank', sets: 3, seconds: 45);

        $exercises = $workout->exercises;
        self::assertCount(2, $exercises);
        self::assertSame('Barbell Bench Press', $exercises[0]->exercise);
        self::assertSame(45, $exercises[1]->seconds);
    }

    public function testItRefusesToPlanBeyondTheLimits(): void
    {
        ($this->kit->createWorkout)('Pecs');

        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessageIsOrContains('Sets must be between');

        ($this->kit->planExercise)('Pecs', 'bench-press', sets: Limits::MAX_SETS + 1, reps: 8);
    }

    public function testItRefusesToPlanOnAnUnknownWorkout(): void
    {
        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessageIsOrContains('list_workouts');

        ($this->kit->planExercise)('Jambes', 'bench-press', sets: 4, reps: 8);
    }

    public function testItRefusesAnUnknownExercise(): void
    {
        ($this->kit->createWorkout)('Pecs');

        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessageIsOrContains('search_exercises');

        ($this->kit->planExercise)('Pecs', 'does-not-exist', sets: 4, reps: 8);
    }

    public function testItRefusesAnExerciseWithNeitherRepsNorSeconds(): void
    {
        ($this->kit->createWorkout)('Pecs');

        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessageIsOrContains('either repetitions or a duration');

        ($this->kit->planExercise)('Pecs', 'bench-press', sets: 4);
    }

    public function testItRewritesALineInPlace(): void
    {
        ($this->kit->createWorkout)('Pecs');
        ($this->kit->planExercise)('Pecs', 'bench-press', sets: 4, reps: 8, weight: 60.0);
        ($this->kit->planExercise)('Pecs', 'plank', sets: 3, seconds: 45);

        $workout = ($this->kit->changeExercise)('Pecs', position: 1, sets: 5, reps: 5, weight: 70.0);

        $line = $workout->exercises[0];
        self::assertSame('Barbell Bench Press', $line->exercise);
        self::assertSame(1, $line->position);
        self::assertSame(5, $line->sets);
        self::assertSame(70.0, $line->weight);
        self::assertSame('Plank', $workout->exercises[1]->exercise);
    }

    public function testItRefusesAChangeThatBreaksTheLine(): void
    {
        ($this->kit->createWorkout)('Pecs');
        ($this->kit->planExercise)('Pecs', 'bench-press', sets: 4, reps: 8, weight: 60.0);

        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessageIsOrContains('repetitions or a duration, not both');

        ($this->kit->changeExercise)('Pecs', position: 1, sets: 4, reps: 8, seconds: 45);
    }

    public function testARefusedChangeLeavesTheLineUntouched(): void
    {
        ($this->kit->createWorkout)('Pecs');
        ($this->kit->planExercise)('Pecs', 'bench-press', sets: 4, reps: 8, weight: 60.0);

        try {
            ($this->kit->changeExercise)('Pecs', position: 1, sets: Limits::MAX_SETS + 1, reps: 8);
            self::fail('Too many sets should be refused.');
        } catch (ToolCallException) {
        }

        $line = ($this->kit->showWorkout)('Pecs')->exercises[0];
        self::assertSame(4, $line->sets);
        self::assertSame(60.0, $line->weight);
    }

    public function testItMovesALineDown(): void
    {
        $this->threeExercises();

        $workout = ($this->kit->moveExercise)('Pecs', position: 1, to: 3);

        self::assertSame(['Plank', 'Barbell Bench Press', 'Barbell Bench Press'], array_map(static fn (PlannedExerciseView $l): string => $l->exercise, $workout->exercises));
        self::assertSame([1, 2, 3], $this->positions($workout));
        self::assertSame(45, $workout->exercises[0]->seconds);
    }

    public function testItMovesALineUp(): void
    {
        $this->threeExercises();

        $workout = ($this->kit->moveExercise)('Pecs', position: 2, to: 1);

        self::assertSame([1, 2, 3], $this->positions($workout));
        self::assertSame(45, $workout->exercises[0]->seconds);
    }

    public function testMovingBeyondTheEndPutsItLast(): void
    {
        $this->threeExercises();

        $workout = ($this->kit->moveExercise)('Pecs', position: 1, to: 99);

        self::assertSame([1, 2, 3], $this->positions($workout));
        self::assertSame('Barbell Bench Press', $workout->exercises[2]->exercise);
        self::assertSame(70.0, $workout->exercises[2]->weight);
    }

    private function threeExercises(): void
    {
        ($this->kit->createWorkout)('Pecs');
        ($this->kit->planExercise)('Pecs', 'bench-press', sets: 4, reps: 8, weight: 70.0);
        ($this->kit->planExercise)('Pecs', 'plank', sets: 3, seconds: 45);
        ($this->kit->planExercise)('Pecs', 'bench-press', sets: 2, reps: 12, weight: 40.0);
    }

    /**
     * @return list<int>
     */
    private function positions(WorkoutSummary $workout): array
    {
        return array_map(static fn (PlannedExerciseView $line): int => $line->position, $workout->exercises);
    }

    public function testItRemovesALineAndClosesTheGap(): void
    {
        ($this->kit->createWorkout)('Pecs');
        ($this->kit->planExercise)('Pecs', 'bench-press', sets: 4, reps: 8, weight: 60.0);
        ($this->kit->planExercise)('Pecs', 'plank', sets: 3, seconds: 45);

        $workout = ($this->kit->removeExercise)('Pecs', position: 1);

        self::assertCount(1, $workout->exercises);
        self::assertSame('Plank', $workout->exercises[0]->exercise);
        self::assertSame(1, $workout->exercises[0]->position);
    }

    public function testItTellsTheModelWhichPositionsExist(): void
    {
        ($this->kit->createWorkout)('Pecs');
        ($this->kit->planExercise)('Pecs', 'bench-press', sets: 4, reps: 8, weight: 60.0);

        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessageIsOrContains('no exercise at position 3');

        ($this->kit->removeExercise)('Pecs', position: 3);
    }

    public function testTheNextExerciseLandsAfterTheLastOneEvenAfterRemovals(): void
    {
        ($this->kit->createWorkout)('Pecs');
        ($this->kit->planExercise)('Pecs', 'bench-press', sets: 4, reps: 8, weight: 60.0);
        ($this->kit->planExercise)('Pecs', 'plank', sets: 3, seconds: 45);
        ($this->kit->removeExercise)('Pecs', position: 1);

        $this->entityManager->clear();
        $workout = ($this->kit->planExercise)('Pecs', 'bench-press', sets: 5, reps: 5, weight: 70.0);

        self::assertSame([1, 2], $this->positions($workout));
    }

    public function testTheRenumberingSurvivesAReload(): void
    {
        ($this->kit->createWorkout)('Pecs');
        ($this->kit->planExercise)('Pecs', 'bench-press', sets: 4, reps: 8, weight: 60.0);
        ($this->kit->planExercise)('Pecs', 'plank', sets: 3, seconds: 45);
        ($this->kit->removeExercise)('Pecs', position: 1);

        $this->entityManager->clear();

        self::assertSame([1], $this->positions(($this->kit->showWorkout)('Pecs')));
    }

    public function testItRenamesAWorkout(): void
    {
        ($this->kit->createWorkout)('Pecs');
        ($this->kit->planExercise)('Pecs', 'bench-press', sets: 4, reps: 8, weight: 60.0);

        $renamed = ($this->kit->renameWorkout)('Pecs', 'Haut du corps');

        self::assertSame('Haut du corps', $renamed->name);
        self::assertCount(1, $renamed->exercises);
        self::assertSame(['Haut du corps'], array_map(static fn (WorkoutSummary $w): string => $w->name, ($this->kit->listWorkouts)()['workouts']));
    }

    public function testItRefusesANameAlreadyTaken(): void
    {
        ($this->kit->createWorkout)('Pecs');
        ($this->kit->createWorkout)('Dos');

        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessageIsOrContains('already exists');

        ($this->kit->renameWorkout)('Dos', 'Pecs');
    }

    public function testRenamingToItsOwnNameIsAccepted(): void
    {
        ($this->kit->createWorkout)('Pecs');

        self::assertSame('Pecs', ($this->kit->renameWorkout)('Pecs', 'Pecs')->name);
    }

    public function testItRefusesANameThatOnlyDiffersByCaseWhenRenaming(): void
    {
        ($this->kit->createWorkout)('Pecs');
        ($this->kit->createWorkout)('Dos');

        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessageIsOrContains('already exists');

        ($this->kit->renameWorkout)('Dos', 'PECS');
    }

    public function testRenamingOnlyTheCaseIsAccepted(): void
    {
        ($this->kit->createWorkout)('Pecs');

        self::assertSame('PECS', ($this->kit->renameWorkout)('Pecs', 'PECS')->name);
    }

    public function testItRefusesToRenameToABlankName(): void
    {
        ($this->kit->createWorkout)('Pecs');

        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessageIsOrContains('needs a name');

        ($this->kit->renameWorkout)('Pecs', '   ');
    }

    public function testItDuplicatesAWorkoutWithItsExercises(): void
    {
        ($this->kit->createWorkout)('Pecs');
        ($this->kit->planExercise)('Pecs', 'bench-press', sets: 4, reps: 8, weight: 60.0);
        ($this->kit->planExercise)('Pecs', 'plank', sets: 3, seconds: 45);

        $listed = ($this->kit->duplicateWorkout)('Pecs', 'Pecs lourd')['workouts'];

        self::assertSame(['Pecs', 'Pecs lourd'], array_map(static fn (WorkoutSummary $w): string => $w->name, $listed));

        $copy = ($this->kit->showWorkout)('Pecs lourd');
        self::assertSame([1, 2], $this->positions($copy));
        self::assertSame('Barbell Bench Press', $copy->exercises[0]->exercise);
        self::assertSame(45, $copy->exercises[1]->seconds);
    }

    public function testTheCopyIsIndependentFromItsSource(): void
    {
        ($this->kit->createWorkout)('Pecs');
        ($this->kit->planExercise)('Pecs', 'bench-press', sets: 4, reps: 8, weight: 60.0);
        ($this->kit->duplicateWorkout)('Pecs', 'Pecs lourd');

        ($this->kit->changeExercise)('Pecs lourd', position: 1, sets: 5, reps: 5, weight: 80.0);

        self::assertSame(4, ($this->kit->showWorkout)('Pecs')->exercises[0]->sets);
        self::assertSame(5, ($this->kit->showWorkout)('Pecs lourd')->exercises[0]->sets);
    }

    public function testItRefusesToDuplicateAWorkoutUnderItsOwnName(): void
    {
        ($this->kit->createWorkout)('Pecs');

        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessageIsOrContains('already exists');

        ($this->kit->duplicateWorkout)('Pecs', 'Pecs');
    }

    public function testItDeletesAWorkout(): void
    {
        ($this->kit->createWorkout)('Pecs');
        ($this->kit->createWorkout)('Dos');

        $listed = ($this->kit->deleteWorkout)('Pecs')['workouts'];

        self::assertSame(['Dos'], array_map(static fn (WorkoutSummary $w): string => $w->name, $listed));
    }

    protected function tearDown(): void
    {
        $this->entityManager->clear();
        parent::tearDown();
    }
}
