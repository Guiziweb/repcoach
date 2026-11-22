<?php

declare(strict_types=1);

namespace App\Tests\Web;

use App\Session\SessionStatus;
use App\Session\WorkoutSession;
use App\Tests\ExerciseFixture;
use App\Tests\ResetsTheDatabase;
use App\Workout\Workout;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class PagesTest extends WebTestCase
{
    use ResetsTheDatabase;

    private KernelBrowser $browser;

    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        $this->browser = self::createClient();
        $this->entityManager = $this->emptyDatabase();
    }

    public function testEveryPageShowsTheIconOfTheApp(): void
    {
        $this->browser->request('GET', '/');

        $icon = $this->browser->getCrawler()->filter('link[rel="icon"]')->attr('href');
        self::assertSame('/favicon.svg', $icon);
        self::assertFileExists(self::getContainer()->getParameter('kernel.project_dir').'/public'.$icon);
    }

    public function testTheMenuOffersToStartASessionWhenNoneIsOpen(): void
    {
        $this->browser->request('GET', '/');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('.menu', 'Start a session');
    }

    public function testTheAppSpeaksFrenchToAFrenchBrowser(): void
    {
        $this->session($this->workout('Pecs'), finished: true);

        $this->browser->request('GET', '/sessions', [], [], ['HTTP_ACCEPT_LANGUAGE' => 'fr-FR,fr;q=0.9']);

        self::assertResponseHeaderSame('Content-Language', 'fr');
        self::assertSelectorTextContains('.kicker', 'Mes séances · Page 1 sur 1');
        self::assertSelectorTextContains('.session-row', '1 série');
    }

    public function testTheMenuLeadsBackToTheOpenSession(): void
    {
        $this->session($this->workout('Pecs'), finished: false);

        $this->browser->request('GET', '/');

        self::assertSelectorTextContains('.menu-item.is-live', 'Session in progress · Pecs');
    }

    public function testTheProgramsOpenOnTheFirstOne(): void
    {
        $this->workout('Pecs');

        $this->browser->request('GET', '/programs');

        self::assertResponseRedirects('/programs/Pecs');
    }

    public function testWithoutAnyProgramThePageSaysSo(): void
    {
        $this->browser->request('GET', '/programs');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('.empty', 'No program yet');
    }

    public function testAProgramShowsItsLines(): void
    {
        $this->workout('Pecs');

        $this->browser->request('GET', '/programs/Pecs');

        self::assertSelectorTextContains('h1', 'Pecs');
        self::assertSelectorTextContains('.plan-line', 'Barbell Bench Press');
    }

    public function testAProgramIsFoundWhateverTheCase(): void
    {
        $this->workout('Pecs');

        $this->browser->request('GET', '/programs/pecs');

        self::assertResponseRedirects('/programs/Pecs', 302);
    }

    public function testAnUnknownProgramLeadsBackToTheList(): void
    {
        $this->browser->request('GET', '/programs/Legs');

        self::assertResponseRedirects('/programs');
    }

    public function testTheSessionsListTheFinishedOnes(): void
    {
        $this->session($this->workout('Pecs'), finished: true);

        $this->browser->request('GET', '/sessions');

        self::assertSelectorTextContains('.session-row', 'Pecs');
        self::assertSelectorTextContains('.session-row', '1 set');
    }

    public function testAFinishedSessionShowsItsSets(): void
    {
        $session = $this->session($this->workout('Pecs'), finished: true);

        $this->browser->request('GET', '/sessions/'.$session->getId());

        self::assertSelectorTextContains('.chip', '8 × 60');
        self::assertSelectorTextContains('.totals', '480');
    }

    public function testARunningSessionIsShownLive(): void
    {
        $session = $this->session($this->workout('Pecs'), finished: false);

        $this->browser->request('GET', '/sessions/'.$session->getId());
        self::assertResponseRedirects('/session');

        $this->browser->followRedirect();
        self::assertSelectorTextContains('h1', 'Pecs');
        self::assertSelectorExists('.chrono[data-chrono-running-value="true"]');
        self::assertSelectorCount(3, '.chip.is-planned');
    }

    public function testWithoutAnOpenSessionTheLiveScreenInvitesToStartOne(): void
    {
        $this->browser->request('GET', '/session');

        self::assertSelectorTextContains('.empty', 'No session in progress');
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

    private function session(Workout $workout, bool $finished): WorkoutSession
    {
        $session = new WorkoutSession(new DateTimeImmutable('2026-11-22 10:00'), $workout);
        ExerciseFixture::set($session, new DateTimeImmutable('2026-11-22 10:05'), reps: 8, weight: 60.0);

        if ($finished) {
            $session->setStatus(SessionStatus::Finished, ['at' => new DateTimeImmutable('2026-11-22 11:00')]);
        }

        $this->entityManager->persist($session);
        $this->entityManager->flush();

        return $session;
    }
}
