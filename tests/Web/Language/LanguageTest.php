<?php

declare(strict_types=1);

namespace App\Tests\Web\Language;

use App\Tests\ResetsTheDatabase;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class LanguageTest extends WebTestCase
{
    use ResetsTheDatabase;

    private const array ENGLISH_BROWSER = ['HTTP_ACCEPT_LANGUAGE' => 'en'];

    private KernelBrowser $browser;

    protected function setUp(): void
    {
        $this->browser = self::createClient();
        $this->emptyDatabase();
    }

    public function testTheBrowserLanguageAppliesUntilOneIsChosen(): void
    {
        $this->browser->request('GET', '/', [], [], ['HTTP_ACCEPT_LANGUAGE' => 'fr']);

        self::assertSelectorTextContains('.menu', 'Mes programmes');
    }

    public function testChoosingALanguageLeadsBackToTheMenuInThatLanguage(): void
    {
        $this->browser->request('GET', '/language/fr', [], [], self::ENGLISH_BROWSER);

        self::assertResponseRedirects('/');
        $this->browser->followRedirect();
        self::assertSelectorTextContains('.menu', 'Mes programmes');
        self::assertSelectorExists('html[lang="fr"]');
    }

    public function testTheChosenLanguageStaysOnEveryScreen(): void
    {
        $this->browser->request('GET', '/language/fr', [], [], self::ENGLISH_BROWSER);

        $this->browser->request('GET', '/sessions', [], [], self::ENGLISH_BROWSER);

        self::assertSelectorTextContains('.screen', 'Mes séances');
    }

    public function testTheMenuShowsWhichLanguageIsOn(): void
    {
        $this->browser->request('GET', '/', [], [], self::ENGLISH_BROWSER);

        self::assertSelectorTextContains('.language[aria-current]', 'EN');
    }

    public function testOnlyTheLanguagesOfTheAppCanBeChosen(): void
    {
        $this->browser->request('GET', '/language/de');

        self::assertResponseStatusCodeSame(404);
    }
}
