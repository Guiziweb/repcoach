<?php

declare(strict_types=1);

namespace App\Tests\Browser;

use App\Tests\ResetsTheDatabase;
use App\Tests\Web\Coach\ScriptedCoach;
use Playwright\Symfony\Test\PlaywrightTestCase;
use Psr\Cache\CacheItemPoolInterface;

use function Playwright\Testing\expect;

final class HelpTest extends PlaywrightTestCase
{
    use ResetsTheDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->emptyDatabase();

        $limits = self::getContainer()->get('cache.rate_limiter');
        self::assertInstanceOf(CacheItemPoolInterface::class, $limits);
        $limits->clear();
    }

    public function testTheHelpGreetsAFirstVisit(): void
    {
        $this->visit('/');

        expect($this->getPage()->locator('.help'))->toBeVisible();
    }

    public function testTheHelpGreetsOnlyOnce(): void
    {
        $this->visit('/');
        $this->click('.help-close');

        $this->visit('/');

        expect($this->getPage()->locator('.help'))->toBeHidden();
    }

    public function testTheQuestionMarkOpensTheHelpAgain(): void
    {
        $this->visit('/');
        $this->click('.help-close');

        $this->click('.hud .help-toggle');

        expect($this->getPage()->locator('.help'))->toBeVisible();
    }

    public function testEscapeClosesTheHelp(): void
    {
        $this->visit('/');

        $this->getPage()->keyboard()->press('Escape');

        expect($this->getPage()->locator('.help'))->toBeHidden();
    }

    public function testASentenceOfTheHelpIsAskedAndTheHelpStaysClosed(): void
    {
        $this->coach()->answers('Here are your last sessions.');
        $this->visit('/');

        $this->click('.help .phrase >> nth=2');

        expect($this->getPage()->locator('#bubble'))->toContainText('Here are your last sessions.');
        expect($this->getPage()->locator('.help'))->toBeHidden();
        self::assertStringContainsString('Show me my last sessions', $this->coach()->lastHeard());
    }

    public function testThePhoneHasItsOwnQuestionMark(): void
    {
        $this->getPage()->setViewportSize(390, 844);
        $this->visit('/');
        $this->click('.help-close');

        $this->click('form.ask .help-toggle');

        expect($this->getPage()->locator('.help'))->toBeVisible();
    }

    private function coach(): ScriptedCoach
    {
        $coach = self::getContainer()->get(ScriptedCoach::class);
        self::assertInstanceOf(ScriptedCoach::class, $coach);

        return $coach;
    }
}
