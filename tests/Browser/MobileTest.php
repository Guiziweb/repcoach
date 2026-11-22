<?php

declare(strict_types=1);

namespace App\Tests\Browser;

use App\Session\SessionStatus;
use App\Session\WorkoutSession;
use App\Tests\ExerciseFixture;
use App\Tests\ResetsTheDatabase;
use App\Tests\Web\Coach\ScriptedCoach;
use App\Workout\Workout;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use Playwright\Symfony\Test\PlaywrightTestCase;
use Psr\Cache\CacheItemPoolInterface;

use function Playwright\Testing\expect;

final class MobileTest extends PlaywrightTestCase
{
    use ResetsTheDatabase;

    private const int WIDTH = 390;

    private const int HEIGHT = 844;

    private const string LONG_NAME = 'Upper body strength and conditioning with a very long name for a phone';

    private const string LONG_ANSWER = 'I can start a session from your programs, log your sets with their load and reps, fix or delete a set, show your past sessions or a program, and create, rename, duplicate or change a program.';

    private const int PEEK = 48;

    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        parent::setUp();
        $this->entityManager = $this->emptyDatabase();

        $limits = self::getContainer()->get('cache.rate_limiter');
        self::assertInstanceOf(CacheItemPoolInterface::class, $limits);
        $limits->clear();
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

    public function testTheCoachSitsAtTheBottomOfThePhone(): void
    {
        $this->onPhone('/');

        $coach = $this->box('#buddy');

        self::assertEqualsWithDelta(self::HEIGHT, $coach['y'] + $coach['height'], 1);
    }

    public function testTheBubbleNeverCoversTheScreen(): void
    {
        $this->workout(self::LONG_NAME);
        $this->onPhone('/programs/'.rawurlencode(self::LONG_NAME));

        $screen = $this->box('#screen');
        $bubble = $this->box('#bubble');

        self::assertLessThanOrEqual($bubble['y'] + 1, $screen['y'] + $screen['height']);
    }

    public function testOnlyTheFirstLineOfTheAnswerWaitsAtTheBottom(): void
    {
        $this->onPhone('/');

        self::assertLessThanOrEqual(self::PEEK, $this->box('#bubble')['height']);
    }

    public function testTheAnswerOpensTheSheet(): void
    {
        $this->onPhone('/');
        $this->answered(self::LONG_ANSWER);

        $this->bubbleSettlesAbove(self::PEEK * 2);
        self::assertGreaterThan(self::PEEK * 2, $this->box('#bubble')['height']);
    }

    public function testTypingFoldsTheSheet(): void
    {
        $this->onPhone('/');
        $this->answered(self::LONG_ANSWER);

        $this->click('form.ask input[name="message"]');

        $this->expectFolded();
    }

    public function testTypingTheNextMessageFoldsTheSheetEvenWhenTheFieldKeptTheFocus(): void
    {
        $this->onPhone('/');
        $this->coach()->answers(self::LONG_ANSWER);
        $this->fill('form.ask input[name="message"]', 'hello');
        $this->getPage()->keyboard()->press('Enter');
        expect($this->getPage()->locator('#buddy'))->toHaveClass('buddy is-open');

        $this->getPage()->keyboard()->type('next');

        $this->expectFolded();
    }

    public function testTouchingTheScreenFoldsTheSheet(): void
    {
        $this->onPhone('/');
        $this->answered(self::LONG_ANSWER);

        $this->click('.menu');

        $this->expectFolded();
    }

    public function testTouchingTheFoldedAnswerOpensItAgain(): void
    {
        $this->onPhone('/');
        $this->answered(self::LONG_ANSWER);
        $this->click('.menu');
        $this->expectFolded();

        $this->click('#bubble');

        expect($this->getPage()->locator('#buddy'))->toHaveClass('buddy is-open');
    }

    public function testTheFoldedAnswerDoesNotFadeAway(): void
    {
        $this->onPhone('/');
        $this->answered(self::LONG_ANSWER);
        $this->click('.menu');
        $this->expectFolded();

        self::assertSame('0px', $this->getPage()->evaluate('() => getComputedStyle(document.querySelector("#bubble p")).getPropertyValue("--fade")'));
    }

    public function testTheWholeAnswerIsReadableInTheSheet(): void
    {
        $this->onPhone('/');
        $this->answered(str_repeat(self::LONG_ANSWER.' ', 12));
        $this->bubbleSettlesAbove(self::PEEK * 4);

        $reading = $this->getPage()->evaluate(<<<'JS'
            () => {
                const bubble = document.querySelector('#bubble');
                const text = bubble.querySelector('p');
                const scrolls = text.scrollHeight > text.clientHeight;
                text.scrollTop = text.scrollHeight;
                const outer = bubble.getBoundingClientRect();
                const inner = text.getBoundingClientRect();

                return {
                    scrolls,
                    reachesTheEnd: text.scrollTop + text.clientHeight >= text.scrollHeight - 1,
                    staysInTheBubble: inner.top >= outer.top - 1 && inner.bottom <= outer.bottom + 1,
                };
            }
            JS);

        self::assertSame(['scrolls' => true, 'reachesTheEnd' => true, 'staysInTheBubble' => true], $reading);
    }

    private function answered(string $answer): void
    {
        $this->coach()->answers($answer);

        $this->fill('form.ask input[name="message"]', 'hello');
        $this->click('form.ask button');

        expect($this->getPage()->locator('#buddy'))->toHaveClass('buddy is-open');
    }

    private function coach(): ScriptedCoach
    {
        $coach = self::getContainer()->get(ScriptedCoach::class);
        self::assertInstanceOf(ScriptedCoach::class, $coach);

        return $coach;
    }

    private function expectFolded(): void
    {
        expect($this->getPage()->locator('#buddy'))->toHaveClass('buddy');
        $this->getPage()->waitForFunction('(peek) => document.querySelector("#bubble").getBoundingClientRect().height <= peek', self::PEEK);
        self::assertLessThanOrEqual(self::PEEK, $this->box('#bubble')['height']);
    }

    private function bubbleSettlesAbove(int $height): void
    {
        $this->getPage()->waitForFunction('(height) => document.querySelector("#bubble").getBoundingClientRect().height > height', $height);
    }

    /**
     * @return array{x: float, y: float, width: float, height: float}
     */
    private function box(string $selector): array
    {
        $box = $this->getPage()->locator($selector)->boundingBox();
        self::assertNotNull($box);

        return $box;
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
