<?php

declare(strict_types=1);

namespace App\Tests\Browser;

use App\Tests\ResetsTheDatabase;
use App\Tests\Web\Coach\ScriptedCoach;
use Playwright\Symfony\Test\PlaywrightTestCase;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\AI\Platform\Result\ToolCall;
use Symfony\AI\Platform\Result\ToolCallResult;

use function Playwright\Testing\expect;

final class LaptopTest extends PlaywrightTestCase
{
    use ResetsTheDatabase;

    private const int WIDTH = 1280;

    private const int HEIGHT = 720;

    private const string THREE_LINES = 'There is no session running, so there is nothing to finish. Your message looks cut off: what exactly do you want to finish?';

    private const string LONG_ANSWER = 'I can start a session from your programs, log your sets with their load and reps, fix or delete a set, show your past sessions or a program, and create, rename, duplicate or change a program.';

    protected function setUp(): void
    {
        parent::setUp();
        $this->emptyDatabase();

        $limits = self::getContainer()->get('cache.rate_limiter');
        self::assertInstanceOf(CacheItemPoolInterface::class, $limits);
        $limits->clear();
    }

    public function testAnAnswerOfAFewLinesShowsWhole(): void
    {
        $this->answered(self::THREE_LINES);

        self::assertSame(0, $this->hiddenText());
    }

    public function testAnAnswerTooLongForTheBubbleFadesWhereItContinues(): void
    {
        $this->answered(str_repeat(self::LONG_ANSWER.' ', 3));

        self::assertGreaterThan(0, $this->hiddenText());
        self::assertTrue($this->fades());
    }

    public function testTheEndOfTheAnswerDoesNotFade(): void
    {
        $this->answered(str_repeat(self::LONG_ANSWER.' ', 3));

        $this->getPage()->evaluate('() => { const text = document.querySelector("#bubble p"); text.scrollTop = text.scrollHeight; }');

        $this->getPage()->waitForFunction('() => "0px" === getComputedStyle(document.querySelector("#bubble p")).getPropertyValue("--fade")');
        self::assertFalse($this->fades());
    }

    public function testAnAnswerThatFitsDoesNotFade(): void
    {
        $this->answered(self::THREE_LINES);

        self::assertFalse($this->fades());
    }

    private function answered(string $answer): void
    {
        $coach = self::getContainer()->get(ScriptedCoach::class);
        self::assertInstanceOf(ScriptedCoach::class, $coach);
        $coach->answers(new ToolCallResult([new ToolCall('toolu_look', 'look_at_screen', [])]), $answer);

        $this->getPage()->setViewportSize(self::WIDTH, self::HEIGHT);
        $this->visit('/');
        $this->fill('form.ask input[name="message"]', 'finish');
        $this->click('form.ask button');

        expect($this->getPage()->locator('#bubble .trail'))->toBeVisible();
        expect($this->getPage()->locator('#bubble p'))->toContainText(mb_substr($answer, 0, 40));
    }

    private function hiddenText(): int
    {
        $hidden = $this->getPage()->evaluate('() => { const text = document.querySelector("#bubble p"); return text.scrollHeight - text.clientHeight; }');
        self::assertIsInt($hidden);

        return $hidden;
    }

    private function fades(): bool
    {
        return '0px' !== $this->getPage()->evaluate('() => getComputedStyle(document.querySelector("#bubble p")).getPropertyValue("--fade")');
    }
}
