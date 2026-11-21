<?php

declare(strict_types=1);

namespace App\Tests\Workout\Mcp;

use App\Exercise\ExerciseRepository;
use App\Shared\Limits;
use App\Tests\ExerciseFixture;
use App\Tests\Workout\Kit;
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

        $exercises = self::getContainer()->get(ExerciseRepository::class);
        self::assertInstanceOf(ExerciseRepository::class, $exercises);

        $this->kit = new Kit($entityManager, $workouts, $exercises);
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

    protected function tearDown(): void
    {
        $this->entityManager->clear();
        parent::tearDown();
    }
}
