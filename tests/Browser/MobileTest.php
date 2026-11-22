<?php

declare(strict_types=1);

namespace App\Tests\Browser;

use App\Session\SessionStatus;
use App\Session\WorkoutSession;
use App\Tests\ExerciseFixture;
use App\Tests\ResetsTheDatabase;
use App\Workout\Workout;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use Playwright\Symfony\Test\PlaywrightTestCase;

final class MobileTest extends PlaywrightTestCase
{
    use ResetsTheDatabase;

    private const int WIDTH = 390;

    private const int HEIGHT = 844;

    private const string LONG_NAME = 'Upper body strength and conditioning with a very long name for a phone';

    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        parent::setUp();
        $this->entityManager = $this->emptyDatabase();
    }

    #[DataProvider('screens')]
    public function testNothingSticksOutOfThePhone(string $screen): void
    {
        $workout = $this->workout(self::LONG_NAME);
        $finished = $this->session($workout, SessionStatus::Finished);
        $this->session($workout, null);

        $this->onPhone(str_replace('{finished}', (string) $finished->getId(), $screen));

        self::assertSame([], $this->getPage()->evaluate(<<<'JS'
            () => [...document.querySelectorAll('body *')]
                .filter((element) => {
                    const box = element.getBoundingClientRect();
                    const spills = 'visible' === getComputedStyle(element).overflowX && element.clientWidth > 0 && element.scrollWidth > element.clientWidth + 1;

                    return box.width > 0 && (box.right > window.innerWidth + 1 || spills);
                })
                .map((element) => `${element.tagName.toLowerCase()}.${[...element.classList].join('.')}`)
            JS));
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function screens(): iterable
    {
        yield 'menu' => ['/'];
        yield 'program' => ['/programs/'.rawurlencode(self::LONG_NAME)];
        yield 'sessions' => ['/sessions'];
        yield 'finished session' => ['/sessions/{finished}'];
        yield 'live session' => ['/session'];
    }

    private function onPhone(string $path): void
    {
        $this->getPage()->setViewportSize(self::WIDTH, self::HEIGHT);
        $this->visit($path);
    }

    private function workout(string $name): Workout
    {
        $exercise = ExerciseFixture::exercise();
        $workout = ExerciseFixture::workout($name, $exercise);
        $this->entityManager->persist($exercise);
        $this->entityManager->persist($workout);
        $this->entityManager->flush();

        return $workout;
    }

    private function session(Workout $workout, ?SessionStatus $closedAs): WorkoutSession
    {
        $session = new WorkoutSession(new DateTimeImmutable(null === $closedAs ? '-10 minutes' : '2026-11-20 10:00'), $workout);
        ExerciseFixture::set($session, new DateTimeImmutable(null === $closedAs ? '-5 minutes' : '2026-11-20 10:05'), reps: 8, weight: 60.0);

        if (null !== $closedAs) {
            $session->setStatus($closedAs, ['at' => new DateTimeImmutable('2026-11-20 11:00')]);
        }

        $this->entityManager->persist($session);
        $this->entityManager->flush();

        return $session;
    }
}
