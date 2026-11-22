<?php

declare(strict_types=1);

namespace App\Tests\Web\Coach;

use App\Tests\ResetsTheDatabase;
use Psr\Cache\CacheItemPoolInterface;
use RuntimeException;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class CoachControllerTest extends WebTestCase
{
    use ResetsTheDatabase;

    private const array SAME_ORIGIN = ['HTTP_ORIGIN' => 'http://localhost'];

    private KernelBrowser $browser;

    protected function setUp(): void
    {
        $this->browser = self::createClient();
        $this->browser->disableReboot();
        $this->emptyDatabase();
        $this->forgetTheLimits();
    }

    public function testTheCoachAnswersInTheBubble(): void
    {
        $this->ask('hello');

        self::assertResponseIsSuccessful();
        self::assertResponseHeaderSame('Content-Type', 'text/vnd.turbo-stream.html; charset=UTF-8');
        self::assertStringContainsString(ScriptedCoach::DEFAULT_ANSWER, (string) $this->browser->getResponse()->getContent());
    }

    public function testAnotherSiteCannotTalkToTheCoach(): void
    {
        $this->browser->request('POST', '/coach', ['_token' => 'csrf-token', 'message' => 'hello', 'screen' => '/'], [], ['HTTP_ORIGIN' => 'https://elsewhere.example']);

        self::assertResponseStatusCodeSame(401);
    }

    public function testAnEmptyMessageIsRefused(): void
    {
        $this->ask('   ');

        self::assertResponseStatusCodeSame(422);
    }

    public function testTheScreenMustBeAPathOfTheApp(): void
    {
        $this->ask('hello', 'https://elsewhere.example/');

        self::assertResponseStatusCodeSame(422);
    }

    public function testABurstOfMessagesIsSlowedDown(): void
    {
        $this->ask('one');
        $this->ask('two');
        $this->ask('three');

        self::assertResponseStatusCodeSame(429);
    }

    public function testForgettingStartsAFreshConversation(): void
    {
        $this->ask('hello');
        $this->browser->request('POST', '/coach/forget', ['_token' => 'csrf-token'], [], self::SAME_ORIGIN);

        self::assertResponseIsSuccessful();
        self::assertStringContainsString('What do we do today?', (string) $this->browser->getResponse()->getContent());
    }

    public function testWhenTheCoachFailsTheBubbleSaysSo(): void
    {
        $this->coach()->answers(new RuntimeException('The model is down.'));

        $this->ask('hello');

        self::assertResponseIsSuccessful();
        self::assertStringContainsString('I could not finish that one', (string) $this->browser->getResponse()->getContent());
    }

    private function coach(): ScriptedCoach
    {
        $coach = self::getContainer()->get(ScriptedCoach::class);
        self::assertInstanceOf(ScriptedCoach::class, $coach);

        return $coach;
    }

    private function ask(string $message, string $screen = '/'): void
    {
        $this->browser->request('POST', '/coach', ['_token' => 'csrf-token', 'message' => $message, 'screen' => $screen], [], self::SAME_ORIGIN);
    }

    private function forgetTheLimits(): void
    {
        $limits = self::getContainer()->get('cache.rate_limiter');
        self::assertInstanceOf(CacheItemPoolInterface::class, $limits);
        $limits->clear();
    }
}
