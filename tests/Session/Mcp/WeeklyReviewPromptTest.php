<?php

declare(strict_types=1);

namespace App\Tests\Session\Mcp;

use App\Session\Mcp\WeeklyReviewPrompt;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Clock\MockClock;

final class WeeklyReviewPromptTest extends TestCase
{
    public function testItReviewsTheLastSevenDaysEndingToday(): void
    {
        $text = $this->text('2026-11-21 09:30:00');

        self::assertStringContainsString('from 2026-11-15 to 2026-11-21', $text);
    }

    public function testTheDatesFollowTheClockAcrossAMonthBoundary(): void
    {
        $text = $this->text('2026-12-03 23:59:00');

        self::assertStringContainsString('from 2026-11-27 to 2026-12-03', $text);
    }

    public function testItNamesTheToolsTheReviewNeeds(): void
    {
        $text = $this->text('2026-11-21 09:30:00');

        self::assertStringContainsString('list_sessions', $text);
        self::assertStringContainsString('show_session', $text);
    }

    private function text(string $now): string
    {
        $messages = (new WeeklyReviewPrompt(new MockClock($now)))();

        self::assertCount(1, $messages);
        self::assertSame('user', $messages[0]['role']);

        return $messages[0]['content'];
    }
}
