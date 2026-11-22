<?php

declare(strict_types=1);

namespace App\Tests\Web\Session\View;

use App\Web\Session\View\Duration;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class DurationTest extends TestCase
{
    #[DataProvider('minutes')]
    public function testItRoundsToTheNearestMinute(int $seconds, int $minutes): void
    {
        self::assertSame($minutes, new Duration($seconds)->minutes());
    }

    /**
     * @return iterable<string, array{int, int}>
     */
    public static function minutes(): iterable
    {
        yield 'nothing' => [0, 0];
        yield 'under half a minute' => [29, 0];
        yield 'half a minute' => [30, 1];
        yield 'two and a half minutes' => [150, 3];
    }

    #[DataProvider('clocks')]
    public function testItReadsLikeAStopwatch(int $seconds, string $clock): void
    {
        self::assertSame($clock, new Duration($seconds)->clock());
    }

    /**
     * @return iterable<string, array{int, string}>
     */
    public static function clocks(): iterable
    {
        yield 'start' => [0, '00:00'];
        yield 'seconds' => [9, '00:09'];
        yield 'minutes' => [754, '12:34'];
        yield 'hours' => [3_725, '1:02:05'];
    }
}
