<?php

declare(strict_types=1);

namespace App\Tests\Session;

use App\Exercise\Exercise;
use App\Session\WorkoutSession;
use App\Session\WorkoutSessionRepository;
use App\Tests\ExerciseFixture;
use App\Workout\Workout;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

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

    public function testItReturnsTheMostRecentOpenSession(): void
    {
        $this->save(new WorkoutSession($this->at('08:00'), $this->workout('Ancienne')));
        $this->save(new WorkoutSession($this->at('10:00'), $this->workout('Récente')));

        self::assertSame('Récente', $this->sessions->open()?->workoutName);
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
