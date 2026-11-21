<?php

declare(strict_types=1);

namespace App\Tests\Workout;

use App\Shared\NotFound;
use App\Shared\Refused;
use App\Tests\ExerciseFixture;
use App\Workout\Workout;
use App\Workout\WorkoutExercise;
use App\Workout\WorkoutRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bridge\Doctrine\Middleware\Debug\DebugDataHolder;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class WorkoutRepositoryTest extends KernelTestCase
{
    private WorkoutRepository $workouts;

    protected function setUp(): void
    {
        self::bootKernel();

        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        self::assertInstanceOf(EntityManagerInterface::class, $entityManager);

        $metadata = $entityManager->getMetadataFactory()->getAllMetadata();
        $schema = new SchemaTool($entityManager);
        $schema->dropSchema($metadata);
        $schema->createSchema($metadata);

        $bench = ExerciseFixture::exercise();
        $entityManager->persist($bench);
        $entityManager->persist(ExerciseFixture::workout('Pecs', $bench));
        $entityManager->persist(new Workout('Épaules'));
        $entityManager->persist(new Workout('Dos'));
        $squat = ExerciseFixture::exercise(id: 'squat', name: 'Back Squat');
        $entityManager->persist($squat);
        $legs = new Workout('Jambes');
        new WorkoutExercise($legs, $squat, 1, sets: 3, reps: 5, weight: 100.0);
        new WorkoutExercise($legs, $bench, 2, sets: 3, reps: 8, weight: 60.0);
        $entityManager->persist($legs);
        $entityManager->flush();
        $entityManager->clear();

        $workouts = self::getContainer()->get(WorkoutRepository::class);
        self::assertInstanceOf(WorkoutRepository::class, $workouts);
        $this->workouts = $workouts;
    }

    public function testItFindsAWorkoutWhateverTheCaseOrTheSpacesAroundIt(): void
    {
        self::assertSame('Pecs', $this->workouts->named('  pECS ')?->name);
    }

    public function testItFindsAnAccentedWorkoutWhateverTheCase(): void
    {
        self::assertSame('Épaules', $this->workouts->named('épaules')?->name);
    }

    public function testItListsTheWorkoutsByNameWithTheirExercises(): void
    {
        $all = $this->workouts->all();

        self::assertSame(['Dos', 'Jambes', 'Pecs', 'Épaules'], array_map(static fn (Workout $workout): string => $workout->name, $all));
        self::assertCount(1, $all[2]->getExercises());
    }

    public function testItRefusesAnUnknownWorkout(): void
    {
        $this->expectException(NotFound::class);
        $this->expectExceptionMessageIsOrContains('No workout named "Legs".');

        $this->workouts->get('Legs');
    }

    public function testItRefusesANameThatOnlyDiffersByCase(): void
    {
        $this->expectException(Refused::class);
        $this->expectExceptionMessageIsOrContains('already exists');

        $this->workouts->ensureFree('PECS');
    }

    public function testAWorkoutMayKeepItsOwnName(): void
    {
        $pecs = $this->workouts->get('Pecs');

        self::assertSame('PECS', $this->workouts->ensureFree('PECS', $pecs));
    }

    public function testListingReadsEveryWorkoutAndItsExercisesInOneQuery(): void
    {
        $queries = $this->queries();
        $names = [];

        foreach ($this->workouts->all() as $workout) {
            foreach ($workout->getExercises() as $line) {
                $names[] = $line->exercise->name;
            }
        }

        self::assertCount(3, $names);
        self::assertSame(1, $this->queries() - $queries);
    }

    public function testFindingOneWorkoutReadsItAndItsExercisesInOneQuery(): void
    {
        $queries = $this->queries();
        $names = [];

        foreach ($this->workouts->get('jambes')->getExercises() as $line) {
            $names[] = $line->exercise->name;
        }

        self::assertSame(['Back Squat', 'Barbell Bench Press'], $names);
        self::assertSame(1, $this->queries() - $queries);
    }

    private function queries(): int
    {
        $holder = self::getContainer()->get('doctrine.debug_data_holder');
        self::assertInstanceOf(DebugDataHolder::class, $holder);

        return \count($holder->getData()['default'] ?? []);
    }
}
