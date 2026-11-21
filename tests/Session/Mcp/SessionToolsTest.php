<?php

declare(strict_types=1);

namespace App\Tests\Session\Mcp;

use App\Session\WorkoutSessionRepository;
use App\Tests\ExerciseFixture;
use App\Tests\Session\Kit;
use App\Workout\WorkoutRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Mcp\Exception\ToolCallException;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Clock\MockClock;

final class SessionToolsTest extends KernelTestCase
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

        $bench = ExerciseFixture::exercise();
        $entityManager->persist($bench);
        $entityManager->persist(ExerciseFixture::workout(exercise: $bench));
        $entityManager->flush();

        $sessions = self::getContainer()->get(WorkoutSessionRepository::class);
        self::assertInstanceOf(WorkoutSessionRepository::class, $sessions);

        $workouts = self::getContainer()->get(WorkoutRepository::class);
        self::assertInstanceOf(WorkoutRepository::class, $workouts);

        $this->kit = new Kit($entityManager, $sessions, $workouts, new MockClock('2026-10-27 10:00:00'));
    }

    public function testItStartsASession(): void
    {
        $summary = ($this->kit->start)('Pecs');

        self::assertSame('Pecs', $summary->workout);
        self::assertSame('running', $summary->status);
        self::assertSame([], $summary->sets);
    }

    public function testItRefusesASecondSession(): void
    {
        ($this->kit->start)('Pecs');

        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessageIsOrContains('already open');

        ($this->kit->start)('Pecs');
    }

    public function testItRefusesAnUnknownWorkout(): void
    {
        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessageIsOrContains('list_workouts');

        ($this->kit->start)('Jambes');
    }

    protected function tearDown(): void
    {
        $this->entityManager->clear();
        parent::tearDown();
    }
}
