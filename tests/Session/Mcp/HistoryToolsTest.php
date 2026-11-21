<?php

declare(strict_types=1);

namespace App\Tests\Session\Mcp;

use App\Exercise\ExerciseRepository;
use App\Session\ClosedStatus;
use App\Session\WorkoutSessionRepository;
use App\Tests\ExerciseFixture;
use App\Tests\Session\Kit;
use App\Workout\WorkoutRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Mcp\Exception\ToolCallException;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Clock\MockClock;

final class HistoryToolsTest extends KernelTestCase
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

        $exercises = self::getContainer()->get(ExerciseRepository::class);
        self::assertInstanceOf(ExerciseRepository::class, $exercises);

        $this->kit = new Kit($entityManager, $sessions, $workouts, $exercises, new MockClock('2026-10-27 10:00:00'), self::getContainer()->get('state_machine.session'));
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

    protected function tearDown(): void
    {
        $this->entityManager->clear();
        parent::tearDown();
    }
}
