<?php

declare(strict_types=1);

namespace App\Tests\Session\Mcp;

use App\Exercise\ExerciseRepository;
use App\Session\WorkoutSessionRepository;
use App\Shared\Limits;
use App\Tests\ExerciseFixture;
use App\Tests\Session\Kit;
use App\Tests\Workout\Kit as WorkoutKit;
use App\Workout\WorkoutRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Mcp\Exception\ToolCallException;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Clock\MockClock;

final class SessionToolsTest extends KernelTestCase
{
    private MockClock $clock;

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

        $this->clock = new MockClock('2026-10-27 10:00:00');
        $this->kit = new Kit($entityManager, $sessions, $workouts, $exercises, $this->clock, self::getContainer()->get('state_machine.session'));
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

    public function testItRefusesASetOutsideThePlanWithBothRepsAndSeconds(): void
    {
        ($this->kit->start)('Pecs');

        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessageIsOrContains('not both');

        ($this->kit->logSet)(exercise: 'plank', reps: 8, seconds: 45);
    }

    public function testItRefusesAnUnknownExercise(): void
    {
        ($this->kit->start)('Pecs');

        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessageIsOrContains('search_exercises');

        ($this->kit->logSet)(exercise: 'does-not-exist', reps: 8);
    }

    public function testItRefusesToLogWithoutASession(): void
    {
        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessageIsOrContains('No session is open');

        ($this->kit->logSet)(position: 1, reps: 8);
    }

    public function testItRefusesASetWithNeitherRepsNorSeconds(): void
    {
        ($this->kit->start)('Pecs');

        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessageIsOrContains('either repetitions or a duration');

        ($this->kit->logSet)(exercise: 'bench-press');
    }

    public function testItRefusesAPositionThePlanDoesNotHave(): void
    {
        ($this->kit->start)('Pecs');

        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessageIsOrContains('no line at position 4');

        ($this->kit->logSet)(position: 4);
    }

    public function testItRefusesASetBeyondTheLimitsAndRecordsNothing(): void
    {
        ($this->kit->start)('Pecs');

        try {
            ($this->kit->logSet)(position: 1, reps: Limits::MAX_REPS + 1, weight: 60.0);
            self::fail('Too many repetitions should be refused.');
        } catch (ToolCallException) {
        }

        self::assertSame([], ($this->kit->current)()->sets);
    }

    public function testItRefusesALiftOutsideThePlanWithoutItsWeight(): void
    {
        ($this->kit->start)('Pecs');

        try {
            ($this->kit->logSet)(exercise: 'bench-press', reps: 8);
            self::fail('A lift without its weight should be refused.');
        } catch (ToolCallException $refused) {
            self::assertStringContainsString('Give the weight', $refused->getMessage());
        }

        self::assertSame([], ($this->kit->current)()->sets);
    }

    public function testItRefusesAnExerciseThatIsNotTheOneOfThePosition(): void
    {
        ($this->kit->start)('Pecs');

        try {
            ($this->kit->logSet)(position: 1, exercise: 'plank', reps: 8);
            self::fail('A mismatching exercise should be refused.');
        } catch (ToolCallException $refused) {
            self::assertStringContainsString('not "plank"', $refused->getMessage());
        }

        self::assertSame([], ($this->kit->current)()->sets);
    }

    public function testTheCurrentSessionShowsWhatWasLogged(): void
    {
        ($this->kit->start)('Pecs');
        $this->clock->sleep(120);
        ($this->kit->logSet)(position: 1, reps: 8, weight: 60.0);
        $this->clock->sleep(90);

        $summary = ($this->kit->current)();

        self::assertSame(210, $summary->durationSeconds);
        $sets = $summary->sets;
        self::assertCount(1, $sets);
        self::assertSame('Barbell Bench Press', $sets[0]->exercise);
    }

    public function testASessionKeepsThePlanItStartedWith(): void
    {
        ($this->kit->start)('Pecs');

        $workouts = self::getContainer()->get(WorkoutRepository::class);
        self::assertInstanceOf(WorkoutRepository::class, $workouts);

        ($this->workoutKit($workouts)->removeExercise)('Pecs', position: 1);

        $summary = ($this->kit->current)();

        self::assertCount(1, $summary->planned);
        self::assertSame('Barbell Bench Press', $summary->planned[0]->exercise);
    }

    public function testWithoutASessionItReportsAnEmptyStateRatherThanFailing(): void
    {
        $summary = ($this->kit->current)();

        self::assertSame('none', $summary->status);
        self::assertSame('', $summary->workout);
        self::assertSame([], $summary->planned);
        self::assertSame([], $summary->sets);
    }

    public function testAPausedSessionStopsTheClockAndRefusesSets(): void
    {
        ($this->kit->start)('Pecs');
        $this->clock->sleep(600);
        $summary = ($this->kit->pause)();

        self::assertSame('paused', $summary->status);

        $this->clock->sleep(1800);
        self::assertSame(600, ($this->kit->current)()->durationSeconds);

        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessageIsOrContains('paused');

        ($this->kit->logSet)(position: 1, reps: 8);
    }

    public function testResumingLetsTheSessionGoOnWithoutCountingThePause(): void
    {
        ($this->kit->start)('Pecs');
        $this->clock->sleep(600);
        ($this->kit->pause)();
        $this->clock->sleep(1800);
        ($this->kit->resume)();
        $this->clock->sleep(300);

        $summary = ($this->kit->logSet)(position: 1, reps: 8, weight: 60.0);

        self::assertSame('running', $summary->status);
        self::assertSame(900, $summary->durationSeconds);
    }

    public function testPausingTwiceKeepsTheFirstPause(): void
    {
        ($this->kit->start)('Pecs');
        $this->clock->sleep(600);
        ($this->kit->pause)();
        $this->clock->sleep(600);
        $again = ($this->kit->pause)();
        $this->clock->sleep(600);

        self::assertSame('paused', $again->status);
        self::assertSame(600, ($this->kit->current)()->durationSeconds);

        ($this->kit->resume)();
        $this->clock->sleep(300);

        self::assertSame(900, ($this->kit->current)()->durationSeconds);
    }

    public function testResumingARunningSessionChangesNothing(): void
    {
        ($this->kit->start)('Pecs');
        $this->clock->sleep(600);

        $resumed = ($this->kit->resume)();

        self::assertSame('running', $resumed->status);
        self::assertSame(600, $resumed->durationSeconds);
    }

    public function testItKeepsTheSessionOpenWhenTheDurationIsImpossible(): void
    {
        ($this->kit->start)('Pecs');

        try {
            ($this->kit->finish)(Limits::MAX_MINUTES + 1);
            self::fail('An impossible duration should be refused.');
        } catch (ToolCallException) {
        }

        self::assertSame('running', ($this->kit->current)()->status);
    }

    public function testTheServerStampsTheTimeNotTheModel(): void
    {
        ($this->kit->start)('Pecs');
        $this->clock->sleep(2700);
        $summary = ($this->kit->finish)();

        self::assertSame(2700, $summary->durationSeconds);
    }

    public function testTheGivenDurationOfAPausedSessionExcludesThePause(): void
    {
        ($this->kit->start)('Pecs');
        $this->clock->sleep(600);
        ($this->kit->pause)();
        $this->clock->sleep(600);
        ($this->kit->resume)();

        $finished = ($this->kit->finish)(durationMinutes: 30);

        self::assertSame(1800, $finished->durationSeconds);
    }

    public function testAWorkoutWithoutExercisesStartsAndFinishesEmpty(): void
    {
        $workouts = self::getContainer()->get(WorkoutRepository::class);
        self::assertInstanceOf(WorkoutRepository::class, $workouts);
        ($this->workoutKit($workouts)->createWorkout)('Vide');

        $started = ($this->kit->start)('Vide');
        $finished = ($this->kit->finish)();

        self::assertSame([], $started->planned);
        self::assertSame([], $finished->sets);
        self::assertSame(0.0, $finished->volume);
        self::assertSame('finished', $finished->status);
    }

    public function testASessionEnteredAfterTheFactTakesTheDurationItWasGiven(): void
    {
        ($this->kit->start)('Pecs');
        $this->clock->sleep(60);

        $summary = ($this->kit->finish)(durationMinutes: 55);

        self::assertSame(3300, $summary->durationSeconds);
    }

    public function testFinishingTwiceIsRefused(): void
    {
        ($this->kit->start)('Pecs');
        ($this->kit->finish)();

        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessageIsOrContains('No session is open');

        ($this->kit->finish)();
    }

    public function testTheEmptyStateIsAlsoWhatItReportsOnceFinished(): void
    {
        ($this->kit->start)('Pecs');
        ($this->kit->finish)();

        self::assertSame('none', ($this->kit->current)()->status);
    }

    public function testAnAbortedSessionFreesTheSlot(): void
    {
        ($this->kit->start)('Pecs');
        $summary = ($this->kit->abort)();

        self::assertSame('aborted', $summary->status);
        self::assertSame('Pecs', ($this->kit->start)('Pecs')->workout);
    }

    private function workoutKit(WorkoutRepository $workouts): WorkoutKit
    {
        $exercises = self::getContainer()->get(ExerciseRepository::class);
        self::assertInstanceOf(ExerciseRepository::class, $exercises);

        return new WorkoutKit($this->entityManager, $workouts, $exercises);
    }

    protected function tearDown(): void
    {
        $this->entityManager->clear();
        parent::tearDown();
    }
}
