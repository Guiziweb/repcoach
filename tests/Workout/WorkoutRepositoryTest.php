<?php

declare(strict_types=1);

namespace App\Tests\Workout;

use App\Shared\NotFound;
use App\Shared\Refused;
use App\Tests\ExerciseFixture;
use App\Workout\Workout;
use App\Workout\WorkoutRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
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
        $entityManager->flush();

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

        self::assertSame(['Dos', 'Pecs', 'Épaules'], array_map(static fn (Workout $workout): string => $workout->name, $all));
        self::assertCount(1, $all[1]->getExercises());
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
}
