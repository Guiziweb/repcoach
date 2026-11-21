<?php

declare(strict_types=1);

namespace App\Tests\Exercise\Import;

use App\Exercise\Enum\BodyPart;
use App\Exercise\Enum\Difficulty;
use App\Exercise\Enum\Equipment;
use App\Exercise\Enum\ExerciseCategory;
use App\Exercise\Enum\ForceType;
use App\Exercise\Enum\Mechanic;
use App\Exercise\Import\ExerciseFactory;
use App\Exercise\Import\InvalidExerciseRow;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ExerciseFactory::class)]
final class ExerciseFactoryTest extends TestCase
{
    public function testItBuildsAnExerciseFromADatasetRow(): void
    {
        $exercise = (new ExerciseFactory())->fromRepDb($this->row());

        self::assertSame('bench-press', $exercise->id);
        self::assertSame('Barbell Bench Press', $exercise->name);
        self::assertSame(ExerciseCategory::Strength, $exercise->category);
        self::assertSame(BodyPart::Chest, $exercise->bodyPart);
        self::assertSame(ForceType::Push, $exercise->forceType);
        self::assertSame(Mechanic::Compound, $exercise->mechanic);
        self::assertSame(Difficulty::Intermediate, $exercise->difficulty);
        self::assertSame(['pectoralis_major'], $exercise->primaryMuscles);
        self::assertSame(['requires_bench'], $exercise->tags);
        self::assertSame(['strength'], $exercise->goals);
        self::assertSame(6.0, $exercise->met);
        self::assertFalse($exercise->bodyweight);
        self::assertFalse($exercise->unilateral);
    }

    public function testItReadsTheEquipment(): void
    {
        $exercise = (new ExerciseFactory())->fromRepDb($this->row());

        self::assertSame(Equipment::Barbell, $exercise->equipment);
        self::assertTrue($exercise->carriesLoad());
    }

    public function testNoEquipmentMeansNothingToLoad(): void
    {
        $exercise = (new ExerciseFactory())->fromRepDb([...$this->row(), 'equipment' => null, 'is_bodyweight' => true]);

        self::assertNull($exercise->equipment);
        self::assertFalse($exercise->carriesLoad());
    }

    public function testAPullUpBarIsNotALoad(): void
    {
        $exercise = (new ExerciseFactory())->fromRepDb([...$this->row(), 'id' => 'pull-up', 'equipment' => 'pull_up_bar']);

        self::assertSame(Equipment::PullUpBar, $exercise->equipment);
        self::assertFalse($exercise->carriesLoad());
    }

    public function testItRefusesEquipmentItDoesNotKnow(): void
    {
        $this->expectException(InvalidExerciseRow::class);
        $this->expectExceptionMessageIsOrContains('bench-press');

        (new ExerciseFactory())->fromRepDb([...$this->row(), 'equipment' => 'jetpack']);
    }

    public function testItDropsValuesOfTheWrongType(): void
    {
        $exercise = (new ExerciseFactory())->fromRepDb([
            ...$this->row(),
            'primary_muscles' => ['pectoralis_major', 42, null],
            'tags' => 'not-a-list',
            'met' => 'heavy',
        ]);

        self::assertSame(['pectoralis_major'], $exercise->primaryMuscles);
        self::assertSame([], $exercise->tags);
        self::assertSame(0.0, $exercise->met);
    }

    public function testItRefusesARowWithoutAName(): void
    {
        $this->expectException(InvalidExerciseRow::class);
        $this->expectExceptionMessageIsOrContains('name_en');

        (new ExerciseFactory())->fromRepDb([...$this->row(), 'name_en' => '']);
    }

    public function testItNamesTheExerciseWhenAValueIsUnknown(): void
    {
        $this->expectException(InvalidExerciseRow::class);
        $this->expectExceptionMessageIsOrContains('bench-press');

        (new ExerciseFactory())->fromRepDb([...$this->row(), 'difficulty' => 'olympian']);
    }

    /**
     * @return array<string, mixed>
     */
    private function row(): array
    {
        return [
            'id' => 'bench-press',
            'name_en' => 'Barbell Bench Press',
            'description_en' => 'A horizontal press performed lying on a bench.',
            'category' => 'strength',
            'body_part' => 'chest',
            'force_type' => 'push',
            'mechanic' => 'compound',
            'difficulty' => 'intermediate',
            'primary_muscles' => ['pectoralis_major'],
            'secondary_muscles' => ['triceps_brachii'],
            'instructions_en' => ['Lie on the bench.', 'Press the bar.'],
            'tags' => ['requires_bench'],
            'goals' => ['strength'],
            'images' => ['flat' => ['start' => 'images/flat/bench-press-start.webp', 'peak' => 'images/flat/bench-press-peak.webp']],
            'is_bodyweight' => false,
            'is_unilateral' => false,
            'met' => 6.0,
            'equipment' => 'barbell',
        ];
    }
}
