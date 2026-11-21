<?php

declare(strict_types=1);

namespace App\Tests\Session\Mcp;

use App\Session\Mcp\CoachSessionPrompt;
use PHPUnit\Framework\TestCase;

final class CoachSessionPromptTest extends TestCase
{
    public function testWithoutAWorkoutItAsksTheModelToSuggestOne(): void
    {
        $text = $this->text(null);

        self::assertStringContainsString('list_workouts', $text);
        self::assertStringContainsString('wait for my choice', $text);
        self::assertStringNotContainsString('Start the workout', $text);
    }

    public function testWithAWorkoutItStartsItWithoutTheSpacesAroundItsName(): void
    {
        self::assertStringContainsString('Start the workout "Pecs" with start_session.', $this->text('  Pecs '));
    }

    public function testABlankWorkoutIsTheSameAsNoWorkout(): void
    {
        self::assertSame($this->text(null), $this->text('   '));
    }

    public function testItNamesTheToolsTheCoachingNeeds(): void
    {
        $text = $this->text('Pecs');

        foreach (['log_set', 'current_session', 'finish_session'] as $tool) {
            self::assertStringContainsString($tool, $text);
        }
    }

    private function text(?string $workout): string
    {
        $messages = (new CoachSessionPrompt())($workout);

        self::assertCount(1, $messages);
        self::assertSame('user', $messages[0]['role']);

        return $messages[0]['content'];
    }
}
