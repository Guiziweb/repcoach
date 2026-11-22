<?php

declare(strict_types=1);

namespace App\Tests\Web\Coach\Display;

use App\Session\SessionStatus;
use App\Session\WorkoutSession;
use App\Tests\ExerciseFixture;
use App\Tests\ResetsTheDatabase;
use App\Web\Coach\Display\Destination;
use App\Web\Program\Coach\DisplayProgramTool;
use App\Web\Session\Coach\DisplayLiveSessionTool;
use App\Web\Session\Coach\DisplaySessionsTool;
use App\Web\Session\Coach\DisplaySessionTool;
use App\Workout\Workout;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class DisplayToolsTest extends KernelTestCase
{
    use ResetsTheDatabase;

    private EntityManagerInterface $entityManager;

    private Destination $destination;

    private Workout $workout;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->entityManager = $this->emptyDatabase();

        $exercise = ExerciseFixture::exercise();
        $this->workout = ExerciseFixture::workout('Pecs', $exercise);
        $this->entityManager->persist($exercise);
        $this->entityManager->persist($this->workout);
        $this->entityManager->flush();

        $this->destination = $this->service(Destination::class);
    }

    public function testAWorkoutIsShownUnderItsExactName(): void
    {
        $answer = $this->service(DisplayProgramTool::class)('pecs');

        self::assertSame('/programs/Pecs', $this->destination->url());
        self::assertSame('The workout "Pecs" is now on screen.', $answer);
    }

    public function testAnUnknownWorkoutStaysOffScreen(): void
    {
        $answer = $this->service(DisplayProgramTool::class)('Legs');

        self::assertNull($this->destination->url());
        self::assertStringContainsString('Use list_workouts', $answer);
    }

    public function testTheListOpensOnTheAskedPage(): void
    {
        $this->service(DisplaySessionsTool::class)(3);

        self::assertSame('/sessions?page=3', $this->destination->url());
    }

    public function testTheListNeverOpensBeforeItsFirstPage(): void
    {
        $this->service(DisplaySessionsTool::class)(0);

        self::assertSame('/sessions?page=1', $this->destination->url());
    }

    public function testAFinishedSessionIsShown(): void
    {
        $session = $this->session(SessionStatus::Finished);

        $this->service(DisplaySessionTool::class)((int) $session->getId());

        self::assertSame('/sessions/'.$session->getId(), $this->destination->url());
    }

    public function testAnOpenSessionIsSentToTheLiveScreen(): void
    {
        $session = $this->session(null);

        $answer = $this->service(DisplaySessionTool::class)((int) $session->getId());

        self::assertNull($this->destination->url());
        self::assertStringContainsString('display_live_session', $answer);
    }

    public function testAnAbortedSessionIsNotShown(): void
    {
        $session = $this->session(SessionStatus::Aborted);

        $answer = $this->service(DisplaySessionTool::class)((int) $session->getId());

        self::assertNull($this->destination->url());
        self::assertStringContainsString('was aborted', $answer);
    }

    public function testAnUnknownSessionIsNotShown(): void
    {
        $answer = $this->service(DisplaySessionTool::class)(999);

        self::assertNull($this->destination->url());
        self::assertStringContainsString('There is no session 999', $answer);
    }

    public function testTheLiveScreenNeedsAnOpenSession(): void
    {
        $answer = $this->service(DisplayLiveSessionTool::class)();

        self::assertNull($this->destination->url());
        self::assertStringContainsString('No session is open', $answer);
    }

    public function testTheOpenSessionIsShownLive(): void
    {
        $this->session(null);

        $this->service(DisplayLiveSessionTool::class)();

        self::assertSame('/session', $this->destination->url());
    }

    private function session(?SessionStatus $closedAs): WorkoutSession
    {
        $session = new WorkoutSession(new DateTimeImmutable('2026-11-22 10:00'), $this->workout);

        if (null !== $closedAs) {
            $session->setStatus($closedAs, ['at' => new DateTimeImmutable('2026-11-22 11:00')]);
        }

        $this->entityManager->persist($session);
        $this->entityManager->flush();

        return $session;
    }

    /**
     * @template T of object
     *
     * @param class-string<T> $class
     *
     * @return T
     */
    private function service(string $class): object
    {
        $service = self::getContainer()->get($class);
        self::assertInstanceOf($class, $service);

        return $service;
    }
}
