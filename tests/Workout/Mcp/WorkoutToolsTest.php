<?php

declare(strict_types=1);

namespace App\Tests\Workout\Mcp;

use App\Shared\Limits;
use App\Tests\Workout\Kit;
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

        $workouts = self::getContainer()->get(WorkoutRepository::class);
        self::assertInstanceOf(WorkoutRepository::class, $workouts);

        $this->kit = new Kit($entityManager, $workouts);
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

    protected function tearDown(): void
    {
        $this->entityManager->clear();
        parent::tearDown();
    }
}
