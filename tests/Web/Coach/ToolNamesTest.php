<?php

declare(strict_types=1);

namespace App\Tests\Web\Coach;

use App\Web\Coach\ToolNames;
use PHPUnit\Framework\TestCase;

final class ToolNamesTest extends TestCase
{
    public function testItDropsThePrefixOfTheMcpTools(): void
    {
        self::assertSame('delete_workout', new ToolNames('repcoach_')->bare('repcoach_delete_workout'));
    }

    public function testItKeepsTheNameOfALocalTool(): void
    {
        self::assertSame('display_program', new ToolNames('repcoach_')->bare('display_program'));
    }

    public function testItShowsTheNameInWords(): void
    {
        self::assertSame('log set', new ToolNames('repcoach_')->shown('repcoach_log_set'));
    }
}
