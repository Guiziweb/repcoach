<?php

declare(strict_types=1);

namespace App\Tests\Web\Coach;

use App\Web\Coach\ToolNames;
use App\Web\Coach\ToolTrail;
use PHPUnit\Framework\TestCase;
use stdClass;
use Symfony\AI\Agent\Toolbox\Event\ToolCallSucceeded;
use Symfony\AI\Agent\Toolbox\ToolResult;
use Symfony\AI\Platform\Result\ToolCall;
use Symfony\AI\Platform\Tool\ExecutionReference;
use Symfony\AI\Platform\Tool\Tool;
use Symfony\AI\Platform\Tool\ToolEffectHints;

final class ToolTrailTest extends TestCase
{
    private ToolTrail $trail;

    protected function setUp(): void
    {
        $this->trail = new ToolTrail(new ToolNames('repcoach_'));
    }

    public function testItListsTheToolsTheCoachUsedWithoutTheirPrefix(): void
    {
        ($this->trail)(self::succeeded('repcoach_list_workouts', readOnly: true));
        ($this->trail)(self::succeeded('display_program', readOnly: true));

        self::assertSame([
            ['name' => 'list workouts', 'count' => 1, 'reads' => true],
            ['name' => 'display program', 'count' => 1, 'reads' => true],
        ], $this->trail->summary());
    }

    public function testItCountsARepeatedTool(): void
    {
        ($this->trail)(self::succeeded('repcoach_log_set', readOnly: false));
        ($this->trail)(self::succeeded('repcoach_log_set', readOnly: false));

        self::assertSame([['name' => 'log set', 'count' => 2, 'reads' => false]], $this->trail->summary());
    }

    public function testReadingOnlyChangesNothing(): void
    {
        ($this->trail)(self::succeeded('repcoach_list_workouts', readOnly: true));

        self::assertFalse($this->trail->wrote());
    }

    public function testOneWriteIsEnoughToChangeTheData(): void
    {
        ($this->trail)(self::succeeded('repcoach_list_workouts', readOnly: true));
        ($this->trail)(self::succeeded('repcoach_rename_workout', readOnly: false));

        self::assertTrue($this->trail->wrote());
    }

    public function testItForgetsEverythingBetweenTwoRequests(): void
    {
        ($this->trail)(self::succeeded('repcoach_rename_workout', readOnly: false));
        $this->trail->reset();

        self::assertSame([], $this->trail->summary());
        self::assertFalse($this->trail->wrote());
    }

    private static function succeeded(string $name, bool $readOnly): ToolCallSucceeded
    {
        $definition = new Tool(new ExecutionReference(self::class), $name, 'A tool.', effectHints: new ToolEffectHints(readOnlyHint: $readOnly));
        $call = new ToolCall('toolu_1', $name);

        return new ToolCallSucceeded(new stdClass(), $definition, [], new ToolResult($call, 'done'));
    }
}
