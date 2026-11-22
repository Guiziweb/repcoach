<?php

declare(strict_types=1);

namespace App\Tests\Web\Session;

use App\Session\SessionStatus;
use App\Session\WorkoutSession;
use App\Tests\ExerciseFixture;
use App\Tests\ResetsTheDatabase;
use App\Web\Session\SessionPages;
use App\Workout\Workout;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class SessionPagesTest extends KernelTestCase
{
    use ResetsTheDatabase;

    private EntityManagerInterface $entityManager;

    private SessionPages $pages;

    private Workout $workout;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->entityManager = $this->emptyDatabase();

        $pages = self::getContainer()->get(SessionPages::class);
        self::assertInstanceOf(SessionPages::class, $pages);
        $this->pages = $pages;

        $exercise = ExerciseFixture::exercise();
        $this->workout = ExerciseFixture::workout('Pecs', $exercise);
        $this->entityManager->persist($exercise);
        $this->entityManager->persist($this->workout);
    }

    public function testTheListShowsTheFinishedSessionsMostRecentFirst(): void
    {
        $this->finished('2026-11-01');
        $this->finished('2026-11-03');
        $this->running('2026-11-04');

        $starts = array_map(
            static fn ($row): string => $row->tally->session->startedAt->format('Y-m-d'),
            iterator_to_array($this->pages->page(1)->getCurrentPageResults(), false),
        );

        self::assertSame(['2026-11-03', '2026-11-01'], $starts);
    }

    public function testAPageBeyondTheListShowsTheLastOne(): void
    {
        $this->finished('2026-11-01');

        self::assertSame(1, $this->pages->page(99)->getCurrentPage());
        self::assertSame(1, $this->pages->page(-3)->getCurrentPage());
    }

    public function testAFinishedSessionKnowsItsNeighboursAndItsPage(): void
    {
        $first = $this->finished('2026-11-01');
        $second = $this->finished('2026-11-02');
        $third = $this->finished('2026-11-03');

        $view = $this->pages->session((int) $second->getId());

        self::assertSame($first, $view?->previous);
        self::assertSame($third, $view->next);
        self::assertSame(1, $view->listPage);
    }

    public function testARunningSessionIsNotShownAsFinished(): void
    {
        $running = $this->running('2026-11-04');

        self::assertNull($this->pages->session((int) $running->getId()));
        self::assertTrue($this->pages->isLive((int) $running->getId()));
    }

    public function testTheLiveScreenShowsTheOpenSession(): void
    {
        $this->running('2026-11-04');

        self::assertSame('Pecs', $this->pages->live()?->session->workoutName);
    }

    public function testTheLiveScreenIsEmptyWithoutAnOpenSession(): void
    {
        $this->finished('2026-11-01');

        self::assertNull($this->pages->live());
    }

    public function testAnUnknownSessionShowsNothing(): void
    {
        self::assertNull($this->pages->session(999));
        self::assertFalse($this->pages->isLive(999));
    }

    private function finished(string $day): WorkoutSession
    {
        $session = $this->running($day);
        $session->setStatus(SessionStatus::Finished, ['at' => new DateTimeImmutable("{$day} 11:00")]);
        $this->entityManager->flush();

        return $session;
    }

    private function running(string $day): WorkoutSession
    {
        $session = new WorkoutSession(new DateTimeImmutable("{$day} 10:00"), $this->workout);
        $this->entityManager->persist($session);
        $this->entityManager->flush();

        return $session;
    }
}
