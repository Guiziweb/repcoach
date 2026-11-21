<?php

declare(strict_types=1);

namespace App\Tests\Session;

use App\Exercise\Exercise;
use App\Session\ClosedStatus;
use App\Session\SessionTransition;
use App\Session\WorkoutSession;
use App\Session\WorkoutSessionRepository;
use App\Tests\ExerciseFixture;
use App\Workout\Workout;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Workflow\WorkflowInterface;

#[CoversClass(WorkoutSessionRepository::class)]
final class WorkoutSessionRepositoryTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;

    private WorkoutSessionRepository $sessions;

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

        $sessions = self::getContainer()->get(WorkoutSessionRepository::class);
        self::assertInstanceOf(WorkoutSessionRepository::class, $sessions);
        $this->sessions = $sessions;
    }

    public function testThereIsNoOpenSessionOnAFreshDatabase(): void
    {
        self::assertNull($this->sessions->open());
    }

    public function testItFindsTheRunningSession(): void
    {
        $this->save(new WorkoutSession($this->at('10:00'), $this->workout('Pecs')));

        self::assertSame('Pecs', $this->sessions->open()?->workoutName);
    }

    public function testAPausedSessionIsStillOpen(): void
    {
        $session = new WorkoutSession($this->at('10:00'), $this->workout('Dos'));
        $this->apply($session, SessionTransition::Pause, '10:20');
        $this->save($session);

        self::assertSame('Dos', $this->sessions->open()?->workoutName);
    }

    public function testAFinishedSessionIsNotOpen(): void
    {
        $session = new WorkoutSession($this->at('10:00'), $this->workout('Jambes'));
        $this->apply($session, SessionTransition::Finish, '11:00');
        $this->save($session);

        self::assertNull($this->sessions->open());
    }

    public function testItReturnsTheMostRecentOpenSession(): void
    {
        $this->save(new WorkoutSession($this->at('08:00'), $this->workout('Ancienne')));
        $this->save(new WorkoutSession($this->at('10:00'), $this->workout('Récente')));

        self::assertSame('Récente', $this->sessions->open()?->workoutName);
    }

    public function testItFiltersByWorkoutNameWhateverTheCaseOfAnAccent(): void
    {
        $session = new WorkoutSession($this->at('10:00'), $this->workout('Épaules'));
        $this->apply($session, SessionTransition::Finish, '11:00');
        $this->save($session);

        self::assertCount(1, $this->sessions->closed(ClosedStatus::Finished, ' épaules ', null, null, 10));
        self::assertSame(1, $this->sessions->countClosed(ClosedStatus::Finished, 'ÉPAULES', null, null));
        self::assertSame(0, $this->sessions->countClosed(ClosedStatus::Finished, 'jambes', null, null));
    }

    private function workout(string $name): Workout
    {
        $exercise = $this->entityManager->find(Exercise::class, 'bench-press');

        if (!$exercise instanceof Exercise) {
            $exercise = ExerciseFixture::exercise();
            $this->entityManager->persist($exercise);
            $this->entityManager->flush();
        }

        $workout = ExerciseFixture::workout($name, $exercise);
        $this->entityManager->persist($workout);

        return $workout;
    }

    private function apply(WorkoutSession $session, SessionTransition $transition, string $time): void
    {
        $workflow = self::getContainer()->get('state_machine.session');
        self::assertInstanceOf(WorkflowInterface::class, $workflow);
        $workflow->apply($session, $transition->value, ['at' => $this->at($time)]);
    }

    private function save(WorkoutSession $session): void
    {
        $this->entityManager->persist($session);
        $this->entityManager->flush();
    }

    private function at(string $time): DateTimeImmutable
    {
        return new DateTimeImmutable("2026-10-27 {$time}:00");
    }
}
