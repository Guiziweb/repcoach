<?php

declare(strict_types=1);

namespace App\Tests\Session\Mcp;

use App\Exercise\ExerciseRepository;
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
        $entityManager->persist(ExerciseFixture::exercise(id: 'plank', name: 'Plank', bodyweight: true));
        $entityManager->persist(ExerciseFixture::workout(exercise: $bench));
        $entityManager->flush();

        $sessions = self::getContainer()->get(WorkoutSessionRepository::class);
        self::assertInstanceOf(WorkoutSessionRepository::class, $sessions);

        $workouts = self::getContainer()->get(WorkoutRepository::class);
        self::assertInstanceOf(WorkoutRepository::class, $workouts);

        $exercises = self::getContainer()->get(ExerciseRepository::class);
        self::assertInstanceOf(ExerciseRepository::class, $exercises);

        $this->kit = new Kit($entityManager, $sessions, $workouts, $exercises, new MockClock('2026-10-27 10:00:00'));
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

    public function testItAcceptsALiftOutsideThePlanWithItsWeight(): void
    {
        ($this->kit->start)('Pecs');

        $summary = ($this->kit->logSet)(exercise: 'bench-press', reps: 8, weight: 50.0);

        self::assertSame(400.0, $summary->volume);
    }

    public function testBodyweightRepetitionsNeedNoWeight(): void
    {
        ($this->kit->start)('Pecs');

        $summary = ($this->kit->logSet)(exercise: 'plank', reps: 10);

        self::assertCount(1, $summary->sets);
        self::assertNull($summary->sets[0]->weight);
    }

    public function testTheMatchingExerciseOfThePositionIsAccepted(): void
    {
        ($this->kit->start)('Pecs');

        $summary = ($this->kit->logSet)(position: 1, exercise: 'bench-press', reps: 8, weight: 60.0);

        self::assertCount(1, $summary->sets);
    }

    public function testItCountsTheVolumeOfLiftedSets(): void
    {
        ($this->kit->start)('Pecs');
        ($this->kit->logSet)(position: 1, reps: 8, weight: 60.0);
        $summary = ($this->kit->logSet)(position: 1, reps: 8, weight: 62.5);

        self::assertSame(980.0, $summary->volume);
    }

    public function testAHoldAddsNoVolume(): void
    {
        ($this->kit->start)('Pecs');
        $summary = ($this->kit->logSet)(exercise: 'plank', seconds: 45);

        self::assertSame(0.0, $summary->volume);
        self::assertCount(1, $summary->sets);
    }

    public function testItReportsProgressAgainstThePlan(): void
    {
        ($this->kit->start)('Pecs');
        $summary = ($this->kit->logSet)(position: 1, reps: 8, weight: 60.0);

        $planned = $summary->planned;
        self::assertCount(1, $planned);
        self::assertSame(4, $planned[0]->sets);
        self::assertSame(1, $planned[0]->done);
    }

    public function testItAcceptsASetOutsideThePlan(): void
    {
        ($this->kit->start)('Pecs');
        $summary = ($this->kit->logSet)(exercise: 'plank', seconds: 45);

        $sets = $summary->sets;
        self::assertCount(1, $sets);

        $planned = $summary->planned;
        self::assertSame(0, $planned[0]->done);
    }

    public function testTickingALineRecordsWhatItPlanned(): void
    {
        ($this->kit->start)('Pecs');

        $summary = ($this->kit->logSet)(position: 1);

        self::assertSame(1, $summary->planned[0]->done);
        self::assertSame(8, $summary->sets[0]->reps);
        self::assertSame(60.0, $summary->sets[0]->weight);
    }

    public function testACorrectionOverridesWhatWasPlanned(): void
    {
        ($this->kit->start)('Pecs');

        $summary = ($this->kit->logSet)(position: 1, reps: 6, weight: 65.0);

        self::assertSame(6, $summary->sets[0]->reps);
        self::assertSame(65.0, $summary->sets[0]->weight);
        self::assertSame(1, $summary->planned[0]->done);
    }

    public function testASetOutsideThePlanCountsForNoLine(): void
    {
        ($this->kit->start)('Pecs');

        $summary = ($this->kit->logSet)(exercise: 'plank', seconds: 45);

        self::assertCount(1, $summary->sets);
        self::assertSame(0, $summary->planned[0]->done);
    }

    protected function tearDown(): void
    {
        $this->entityManager->clear();
        parent::tearDown();
    }
}
