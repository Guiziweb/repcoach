<?php

declare(strict_types=1);

namespace App\Tests\Web\Coach;

use App\Web\Coach\DailyTokens;
use PHPUnit\Framework\TestCase;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\RateLimiter\Storage\InMemoryStorage;

final class DailyTokensTest extends TestCase
{
    private DailyTokens $daily;

    protected function setUp(): void
    {
        $this->daily = new DailyTokens(new RateLimiterFactory(['id' => 'coach_tokens', 'policy' => 'fixed_window', 'limit' => 1_000, 'interval' => '1 day'], new InMemoryStorage()));
    }

    public function testTokensAreLeftAtFirst(): void
    {
        self::assertTrue($this->daily->left());
    }

    public function testTokensAreLeftUntilTheBudgetIsSpent(): void
    {
        $this->daily->spend(999);

        self::assertTrue($this->daily->left());
    }

    public function testNoneAreLeftOnceTheBudgetIsSpent(): void
    {
        $this->daily->spend(400);
        $this->daily->spend(600);

        self::assertFalse($this->daily->left());
    }

    public function testAnAnswerLargerThanWhatIsLeftSpendsTheRest(): void
    {
        $this->daily->spend(900);
        $this->daily->spend(5_000);

        self::assertFalse($this->daily->left());
    }
}
