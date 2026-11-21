<?php

declare(strict_types=1);

namespace App\Tests\Session\Mcp;

use App\Exercise\ExerciseRepository;
use App\Session\ClosedStatus;
use App\Session\Mcp\SessionSummary;
use App\Session\WorkoutSessionRepository;
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
