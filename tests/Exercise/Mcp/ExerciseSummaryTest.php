<?php

declare(strict_types=1);

namespace App\Tests\Exercise\Mcp;

use App\Exercise\Enum\BodyPart;
use App\Exercise\Enum\Difficulty;
use App\Exercise\Enum\Equipment;
use App\Exercise\Enum\ExerciseCategory;
use App\Exercise\Enum\ForceType;
use App\Exercise\Enum\Mechanic;
use App\Exercise\Exercise;
use App\Exercise\Mcp\ExerciseSummary;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ExerciseSummary::class)]
final class ExerciseSummaryTest extends TestCase
{
    public function testItKeepsOnlyWhatTheModelNeedsToChoose(): void
    {
        self::assertSame(
            [
                'id' => 'bench-press',
                'name' => 'Barbell Bench Press',
                'bodyPart' => 'chest',
                'primaryMuscles' => ['pectoralis_major'],
                'secondaryMuscles' => ['triceps_brachii'],
                'tags' => ['requires_bench'],
                'bodyweight' => false,
                'equipment' => 'barbell',
            ],
            $this->sent(),
        );
    }

    public function testItKeepsPrimaryAndSecondaryApart(): void
    {
        $summary = $this->sent();

        self::assertSame(['pectoralis_major'], $summary['primaryMuscles']);
        self::assertSame(['triceps_brachii'], $summary['secondaryMuscles']);
    }

    public function testItLeavesOutTheLongFields(): void
    {
        $serialized = $this->sent();

        self::assertArrayNotHasKey('description', $serialized);
        self::assertArrayNotHasKey('instructions', $serialized);
        self::assertArrayNotHasKey('images', $serialized);
    }

    public function testItSerializesExactlyWhatItsSchemaAnnounces(): void
    {
        self::assertSame(
            ExerciseSummary::SCHEMA['required'],
            array_keys($this->sent()),
        );
    }

    /**
     * @return array<array-key, mixed>
     */
    private function sent(): array
    {
        $decoded = json_decode(json_encode(ExerciseSummary::of($this->exercise()), \JSON_THROW_ON_ERROR), true, 512, \JSON_THROW_ON_ERROR);
        self::assertIsArray($decoded);

        return $decoded;
    }

    private function exercise(): Exercise
    {
        return new Exercise(
            id: 'bench-press',
            name: 'Barbell Bench Press',
            description: str_repeat('A long description. ', 20),
            category: ExerciseCategory::Strength,
            bodyPart: BodyPart::Chest,
            forceType: ForceType::Push,
            mechanic: Mechanic::Compound,
            difficulty: Difficulty::Intermediate,
            primaryMuscles: ['pectoralis_major'],
            secondaryMuscles: ['triceps_brachii'],
            instructions: ['Lie down.', 'Press.'],
            tags: ['requires_bench'],
            goals: ['strength'],
            images: ['flat' => ['start' => 'a.webp', 'peak' => 'b.webp']],
            bodyweight: false,
            unilateral: false,
            met: 6.0,
            equipment: Equipment::Barbell,
        );
    }
}
