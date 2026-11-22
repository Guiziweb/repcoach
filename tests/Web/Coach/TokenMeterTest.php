<?php

declare(strict_types=1);

namespace App\Tests\Web\Coach;

use App\Web\Coach\TokenMeter;
use PHPUnit\Framework\TestCase;
use Symfony\AI\Platform\TokenUsage\TokenUsage;
use Symfony\AI\Platform\TokenUsage\TokenUsageAggregation;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

final class TokenMeterTest extends TestCase
{
    private TokenMeter $meter;

    protected function setUp(): void
    {
        $request = new Request();
        $request->setSession(new Session(new MockArraySessionStorage()));
        $requests = new RequestStack();
        $requests->push($request);

        $this->meter = new TokenMeter($requests);
    }

    public function testTheContextCountsTheCachedAndTheFreshTokens(): void
    {
        $consumption = $this->meter->measure(new TokenUsage(promptTokens: 100, completionTokens: 20, cacheCreationTokens: 50, cacheReadTokens: 900));

        self::assertSame(1_050, $consumption?->context);
        self::assertSame(1_070, $consumption->reply);
        self::assertSame(900, $consumption->cached);
    }

    public function testTheContextIsTheOneOfTheLastCall(): void
    {
        $consumption = $this->meter->measure(new TokenUsageAggregation([
            new TokenUsage(promptTokens: 1_000, completionTokens: 50),
            new TokenUsage(promptTokens: 1_200, completionTokens: 30),
        ]));

        self::assertSame(1_200, $consumption?->context);
        self::assertSame(2_280, $consumption->reply);
    }

    public function testTheConversationAddsUpTheReplies(): void
    {
        $this->meter->measure(new TokenUsage(promptTokens: 100, completionTokens: 10));
        $consumption = $this->meter->measure(new TokenUsage(promptTokens: 200, completionTokens: 20));

        self::assertSame(330, $consumption?->conversation);
    }

    public function testForgettingRestartsTheCount(): void
    {
        $this->meter->measure(new TokenUsage(promptTokens: 100, completionTokens: 10));
        $this->meter->reset();

        self::assertSame(220, $this->meter->measure(new TokenUsage(promptTokens: 200, completionTokens: 20))?->conversation);
    }

    public function testItRemembersTheContextOfTheLastAnswer(): void
    {
        $this->meter->measure(new TokenUsage(promptTokens: 100, cacheReadTokens: 900));
        $this->meter->measure(new TokenUsage(promptTokens: 300, cacheReadTokens: 1_700));

        self::assertSame(2_000, $this->meter->context());
    }

    public function testForgettingEmptiesTheContext(): void
    {
        $this->meter->measure(new TokenUsage(promptTokens: 100));
        $this->meter->reset();

        self::assertSame(0, $this->meter->context());
    }

    public function testNothingIsMeasuredWithoutAUsage(): void
    {
        self::assertNull($this->meter->measure(null));
    }
}
