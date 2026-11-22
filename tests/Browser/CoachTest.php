<?php

declare(strict_types=1);

namespace App\Tests\Browser;

use App\Session\WorkoutSession;
use App\Tests\ExerciseFixture;
use App\Tests\ResetsTheDatabase;
use App\Tests\Web\Coach\ScriptedCoach;
use App\Workout\Workout;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Playwright\Symfony\Test\PlaywrightTestCase;
use Psr\Cache\CacheItemPoolInterface;

use function Playwright\Testing\expect;

final class CoachTest extends PlaywrightTestCase
{
    use ResetsTheDatabase;

    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        parent::setUp();
        $this->entityManager = $this->emptyDatabase();

        $limits = self::getContainer()->get('cache.rate_limiter');
        self::assertInstanceOf(CacheItemPoolInterface::class, $limits);
        $limits->clear();
    }

    public function testTheBubbleShowsTheAnswerOfTheCoach(): void
    {
        $this->visit('/');

        $this->fill('form.ask input[name="message"]', 'hello');
        $this->click('form.ask button');

        expect($this->getPage()->locator('#bubble'))->toContainText(ScriptedCoach::DEFAULT_ANSWER);
        expect($this->getPage()->locator('form.ask input[name="message"]'))->toHaveValue('');
    }

    public function testTheMenuOpensAScreenWithoutReloadingTheCoach(): void
    {
        $this->workout('Pecs');
        $this->visit('/');
        $this->send('hello');
        expect($this->getPage()->locator('#bubble'))->toContainText(ScriptedCoach::DEFAULT_ANSWER);
        $this->getPage()->evaluate('() => { window.stillTheSamePage = true; }');

        $this->click('.menu-item[href="/programs"]');

        expect($this->getPage()->locator('h1'))->toContainText('Pecs');
        expect($this->getPage())->toHaveURL('http://localhost/programs/Pecs');
        self::assertTrue($this->getPage()->evaluate('() => true === window.stillTheSamePage'));
        expect($this->getPage()->locator('#bubble'))->toContainText(ScriptedCoach::DEFAULT_ANSWER);
    }

    public function testAMessageSentTwiceInARowLeavesOnce(): void
    {
        $this->visit('/');
        $this->getPage()->evaluate(<<<'JS'
            () => {
                window.coachRequests = 0;
                const send = window.fetch;
                window.fetch = (input, init) => {
                    if (String(input.url ?? input).endsWith('/coach')) {
                        window.coachRequests++;
                    }

                    return send(input, init);
                };
                const form = document.querySelector('form.ask');
                form.querySelector('input[name="message"]').value = 'hello';
                form.requestSubmit();
                form.requestSubmit();
            }
            JS);

        expect($this->getPage()->locator('#bubble'))->toContainText(ScriptedCoach::DEFAULT_ANSWER);
        self::assertSame(1, $this->getPage()->evaluate('() => window.coachRequests'));
    }

    public function testTheChronoOfTheRunningSessionTicks(): void
    {
        $session = new WorkoutSession(new DateTimeImmutable('-5 minutes'), $this->workout('Pecs'));
        $this->entityManager->persist($session);
        $this->entityManager->flush();

        $this->visit('/session');
        $before = $this->getPage()->locator('.chrono')->textContent();

        expect($this->getPage()->locator('.chrono'))->not()->toHaveText((string) $before);
    }

    private function send(string $message): void
    {
        $this->fill('form.ask input[name="message"]', $message);
        $this->click('form.ask button');
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
}
