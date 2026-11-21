<?php

declare(strict_types=1);

namespace App\Tests\Session\Mcp;

use App\Exercise\ExerciseRepository;
use App\Session\ClosedStatus;
use App\Session\Mcp\SessionSummary;
use App\Session\PerformedSetRepository;
use App\Session\WorkoutSessionRepository;
use App\Shared\Limits;
use App\Tests\ExerciseFixture;
use App\Tests\Session\Kit;
use App\Workout\WorkoutRepository;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Mcp\Exception\ToolCallException;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Clock\MockClock;

final class HistoryToolsTest extends KernelTestCase
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
        $entityManager->persist(ExerciseFixture::exercise(id: 'pull-up', name: 'Pull-Up', bodyweight: true));
        $entityManager->persist(ExerciseFixture::workout(exercise: $bench));
        $entityManager->flush();

        $sessions = self::getContainer()->get(WorkoutSessionRepository::class);
        self::assertInstanceOf(WorkoutSessionRepository::class, $sessions);

        $workouts = self::getContainer()->get(WorkoutRepository::class);
        self::assertInstanceOf(WorkoutRepository::class, $workouts);

        $sets = self::getContainer()->get(PerformedSetRepository::class);
        self::assertInstanceOf(PerformedSetRepository::class, $sets);

        $exercises = self::getContainer()->get(ExerciseRepository::class);
        self::assertInstanceOf(ExerciseRepository::class, $exercises);

        $this->clock = new MockClock('2026-10-27 10:00:00');
        $this->kit = new Kit($entityManager, $sessions, $workouts, $sets, $exercises, $this->clock, self::getContainer()->get('state_machine.session'));
    }

    public function testAnAbortedSessionCanBeFoundToo(): void
    {
        ($this->kit->start)('Pecs');
        $aborted = ($this->kit->abort)();

        self::assertSame(0, ($this->kit->listSessions)()['matching']);

        $listed = ($this->kit->listSessions)(status: ClosedStatus::Aborted);

        self::assertSame(1, $listed['matching']);
        self::assertSame($aborted->id, $listed['sessions'][0]->id);
        self::assertSame('aborted', $listed['sessions'][0]->status);
    }

    #[DataProvider('unreadableDays')]
    public function testItRefusesADayItCannotRead(string $day): void
    {
        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessageIsOrContains('YYYY-MM-DD');

        ($this->kit->listSessions)(from: $day);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function unreadableDays(): iterable
    {
        yield 'nothing at all' => [''];
        yield 'a word' => ['monday'];
        yield 'a day that does not exist' => ['2026-02-30'];
        yield 'a date with a time' => ['2026-10-27T10:00:00+00:00'];
    }

    public function testItRefusesAnEmptyBoundOfTheListing(): void
    {
        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessageIsOrContains('YYYY-MM-DD');

        ($this->kit->listSessions)(to: '');
    }

    public function testItListsFinishedSessionsMostRecentFirst(): void
    {
        $this->performed();
        $this->clock->sleep(86400);
        $second = $this->performed();

        $listed = ($this->kit->listSessions)();

        self::assertSame(2, $listed['matching']);
        self::assertSame($second->id, $listed['sessions'][0]->id);
    }

    public function testAListedSessionIsAOneLineSummary(): void
    {
        $done = $this->performed();

        $line = ($this->kit->listSessions)()['sessions'][0];

        self::assertSame($done->id, $line->id);
        self::assertSame('Pecs', $line->workout);
        self::assertSame(1, $line->sets);
        self::assertSame($done->volume, $line->volume);
        self::assertSame($done->durationSeconds, $line->durationSeconds);
    }

    public function testItStopsAtTheLimitButCountsEverySession(): void
    {
        $this->performed();
        $this->clock->sleep(86400);
        $this->performed();
        $this->clock->sleep(86400);
        $latest = $this->performed();

        $listed = ($this->kit->listSessions)(limit: 2);

        self::assertSame(3, $listed['matching']);
        self::assertCount(2, $listed['sessions']);
        self::assertSame($latest->id, $listed['sessions'][0]->id);
    }

    public function testItFindsTheSessionsOfOneDay(): void
    {
        $this->performed();
        $this->clock->sleep(86400);
        $second = $this->performed();

        $listed = ($this->kit->listSessions)(from: '2026-10-28', to: '2026-10-28');

        self::assertSame(1, $listed['matching']);
        self::assertSame($second->id, $listed['sessions'][0]->id);
    }

    public function testTheLastDayIsIncluded(): void
    {
        $this->performed();

        self::assertSame(1, ($this->kit->listSessions)(to: '2026-10-27')['matching']);
        self::assertSame(0, ($this->kit->listSessions)(to: '2026-10-26')['matching']);
        self::assertSame(0, ($this->kit->listSessions)(from: '2026-10-28')['matching']);
    }

    public function testItFiltersByWorkoutWhateverTheCase(): void
    {
        $this->performed();

        self::assertSame(1, ($this->kit->listSessions)(workout: 'pecs')['matching']);
        self::assertSame(0, ($this->kit->listSessions)(workout: 'Legs')['matching']);
    }

    public function testItShowsOneSessionInFull(): void
    {
        $done = $this->performed();

        $shown = ($this->kit->showSession)($done->id);

        self::assertCount(1, $shown->sets);
        self::assertNotEmpty($shown->planned);
    }

    public function testItRefusesToShowAnUnknownSession(): void
    {
        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessageIsOrContains('list_sessions');

        ($this->kit->showSession)(999);
    }

    public function testItCorrectsHowLongASessionLasted(): void
    {
        $done = $this->performed(seconds: 57);

        $corrected = ($this->kit->updateSession)($done->id, startedAt: '2026-10-27T09:05:00+00:00', durationMinutes: 55);

        self::assertSame(3300, $corrected->durationSeconds);
    }

    public function testMovingTheStartKeepsTheDuration(): void
    {
        $done = $this->performed(seconds: 1800);

        $moved = ($this->kit->updateSession)($done->id, startedAt: '2026-10-26T18:00:00+00:00');

        self::assertSame('2026-10-26T18:00:00+00:00', $moved->startedAt);
        self::assertSame(1800, $moved->durationSeconds);
    }

    public function testAStartGivenInAnotherTimezoneKeepsTheSameInstant(): void
    {
        $done = $this->performed(seconds: 1800);

        ($this->kit->updateSession)($done->id, startedAt: '2026-10-26T18:00:00+01:00');
        $reloaded = ($this->kit->showSession)($done->id);

        self::assertSame(
            (new DateTimeImmutable('2026-10-26T18:00:00+01:00'))->getTimestamp(),
            (new DateTimeImmutable($reloaded->startedAt))->getTimestamp(),
        );
        self::assertSame(1800, $reloaded->durationSeconds);
    }

    public function testSessionsStartingTogetherComeBackNewestFirst(): void
    {
        $first = $this->performed();
        $this->clock->sleep(3600);
        $second = $this->performed();
        ($this->kit->updateSession)($first->id, startedAt: '2026-10-26T10:00:00+00:00');
        ($this->kit->updateSession)($second->id, startedAt: '2026-10-26T10:00:00+00:00');

        $listed = ($this->kit->listSessions)(limit: 1);

        self::assertSame($second->id, $listed['sessions'][0]->id);
    }

    public function testItRefusesToCorrectASessionStillOpen(): void
    {
        $open = ($this->kit->start)('Pecs');

        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessageIsOrContains('still open');

        ($this->kit->updateSession)($open->id, durationMinutes: 30);
    }

    public function testItRefusesADateItCannotRead(): void
    {
        $done = $this->performed();

        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessageIsOrContains('ISO 8601');

        ($this->kit->updateSession)($done->id, startedAt: 'hier soir');
    }

    #[DataProvider('unusableStarts')]
    public function testItRefusesAStartThatIsNotAnExactMomentInThePast(string $moment): void
    {
        $done = $this->performed();

        $this->expectException(ToolCallException::class);

        ($this->kit->updateSession)($done->id, startedAt: $moment);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function unusableStarts(): iterable
    {
        yield 'nothing at all' => [''];
        yield 'a relative word' => ['tomorrow'];
        yield 'no offset' => ['2026-10-26T18:00:00'];
        yield 'a day that does not exist' => ['2026-02-30T10:00:00+01:00'];
        yield 'the future' => ['2026-10-28T10:00:00+00:00'];
        yield 'a year beyond four digits' => ['+99999-01-01T00:00:00Z'];
    }

    public function testItRefusesAnEndThatWouldFallInTheFuture(): void
    {
        $done = $this->performed(seconds: 1800);

        try {
            ($this->kit->updateSession)($done->id, startedAt: '2026-10-27T10:10:00+00:00');
            self::fail('A session cannot end after now.');
        } catch (ToolCallException $refused) {
            self::assertStringContainsString('end in the future', $refused->getMessage());
        }

        self::assertSame($done->startedAt, ($this->kit->showSession)($done->id)->startedAt);
    }

    public function testItRefusesALongerDurationThatWouldEndInTheFuture(): void
    {
        $done = $this->performed(seconds: 60);

        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessageIsOrContains('end in the future');

        ($this->kit->updateSession)($done->id, startedAt: '2026-10-27T09:59:00+00:00', durationMinutes: 120);
    }

    public function testItRefusesACorrectionThatChangesNothing(): void
    {
        $done = $this->performed();

        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessageIsOrContains('nothing to change');

        ($this->kit->updateSession)($done->id);
    }

    public function testItRefusesADurationBeyondALongWorkout(): void
    {
        $done = $this->performed();

        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessageIsOrContains('between 1 and');

        ($this->kit->updateSession)($done->id, durationMinutes: Limits::MAX_MINUTES + 1);
    }

    public function testAnUnknownIdSaysHowToFindOne(): void
    {
        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessageIsOrContains('list_sessions');

        ($this->kit->updateSession)(999, durationMinutes: 30);
    }

    public function testItPointsToResumeWhenAForgottenSetLandsOnAPausedSession(): void
    {
        $paused = ($this->kit->start)('Pecs');
        ($this->kit->pause)();

        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessageIsOrContains('Resume it');

        ($this->kit->addSet)($paused->id, position: 1, reps: 8, weight: 60.0);
    }

    public function testItRefusesAForgottenSetBeyondTheLimits(): void
    {
        $done = $this->performed();

        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessageIsOrContains('weight must be between');

        ($this->kit->addSet)($done->id, position: 1, reps: 8, weight: Limits::MAX_WEIGHT + 1);
    }

    public function testItAddsAForgottenSetWithWhatTheLinePlanned(): void
    {
        $done = $this->performed();

        $completed = ($this->kit->addSet)($done->id, position: 1);

        self::assertCount(2, $completed->sets);
        self::assertSame(8, $completed->sets[1]->reps);
        self::assertSame(60.0, $completed->sets[1]->weight);
        self::assertSame(2, $completed->planned[0]->done);
    }

    public function testARefusedSetCorrectionLeavesTheSetUntouched(): void
    {
        $done = $this->performed();

        try {
            ($this->kit->changeSet)($done->sets[0]->id, reps: Limits::MAX_REPS + 1, weight: 65.0);
            self::fail('Too many repetitions should be refused.');
        } catch (ToolCallException) {
        }

        $reloaded = ($this->kit->showSession)($done->id);
        self::assertSame($done->sets[0]->reps, $reloaded->sets[0]->reps);
    }

    public function testChangingTheRepsKeepsTheWeight(): void
    {
        $done = $this->performed();

        $corrected = ($this->kit->changeSet)($done->sets[0]->id, reps: 7);

        self::assertSame(7, $corrected->sets[0]->reps);
        self::assertSame(60.0, $corrected->sets[0]->weight);
    }

    public function testChangingTheWeightKeepsTheReps(): void
    {
        $done = $this->performed();

        $corrected = ($this->kit->changeSet)($done->sets[0]->id, weight: 70.0);

        self::assertSame($done->sets[0]->reps, $corrected->sets[0]->reps);
        self::assertSame(70.0, $corrected->sets[0]->weight);
    }

    public function testZeroRemovesTheLoadFromABodyweightSet(): void
    {
        ($this->kit->start)('Pecs');
        $logged = ($this->kit->logSet)(exercise: 'pull-up', reps: 10, weight: 20.0);
        $sets = $logged->sets;
        $set = end($sets);
        self::assertNotFalse($set);
        self::assertSame(20.0, $set->weight);

        $corrected = ($this->kit->changeSet)($set->id, weight: 0.0);
        $changedSets = $corrected->sets;
        $changed = end($changedSets);
        self::assertNotFalse($changed);

        self::assertNull($changed->weight);
        self::assertSame(10, $changed->reps);
    }

    public function testGivingSecondsReplacesTheRepsOfASet(): void
    {
        ($this->kit->start)('Pecs');
        $logged = ($this->kit->logSet)(exercise: 'pull-up', reps: 10);
        $sets = $logged->sets;
        $set = end($sets);
        self::assertNotFalse($set);

        $corrected = ($this->kit->changeSet)($set->id, seconds: 30);
        $changedSets = $corrected->sets;
        $changed = end($changedSets);
        self::assertNotFalse($changed);

        self::assertNull($changed->reps);
        self::assertSame(30, $changed->seconds);
    }

    public function testItRefusesACorrectionThatChangesNothingInASet(): void
    {
        $done = $this->performed();

        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessageIsOrContains('nothing to change');

        ($this->kit->changeSet)($done->sets[0]->id);
    }

    public function testItRewritesASet(): void
    {
        $done = $this->performed();

        $corrected = ($this->kit->changeSet)($done->sets[0]->id, reps: 6, weight: 65.0);

        self::assertSame(6, $corrected->sets[0]->reps);
        self::assertSame(65.0, $corrected->sets[0]->weight);
        self::assertSame(390.0, $corrected->volume);
    }

    public function testItRefusesASetWithRepsAndSeconds(): void
    {
        $done = $this->performed();

        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessageIsOrContains('not both');

        ($this->kit->changeSet)($done->sets[0]->id, reps: 8, seconds: 45);
    }

    public function testItRemovesASetEnteredByMistake(): void
    {
        $done = $this->performed();

        $emptied = ($this->kit->removeSet)($done->sets[0]->id);

        self::assertSame([], $emptied->sets);
        self::assertSame(0, $emptied->planned[0]->done);
    }

    private function performed(int $seconds = 60): SessionSummary
    {
        ($this->kit->start)('Pecs');
        ($this->kit->logSet)(position: 1);
        $this->clock->sleep($seconds);

        $finished = ($this->kit->finish)();

        return ($this->kit->showSession)($finished->id);
    }

    protected function tearDown(): void
    {
        $this->entityManager->clear();
        parent::tearDown();
    }
}
