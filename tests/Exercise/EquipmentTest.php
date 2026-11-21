<?php

declare(strict_types=1);

namespace App\Tests\Exercise;

use App\Exercise\Enum\Equipment;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class EquipmentTest extends TestCase
{
    #[DataProvider('loaded')]
    public function testSomeEquipmentIsLiftedWithAWeight(Equipment $equipment): void
    {
        self::assertTrue($equipment->carriesLoad());
    }

    /**
     * @return iterable<string, array{Equipment}>
     */
    public static function loaded(): iterable
    {
        yield 'a barbell' => [Equipment::Barbell];
        yield 'dumbbells' => [Equipment::Dumbbell];
        yield 'a kettlebell' => [Equipment::Kettlebell];
        yield 'a cable' => [Equipment::Cable];
        yield 'a smith machine' => [Equipment::SmithMachine];
        yield 'a leg press' => [Equipment::LegPress];
        yield 'any machine' => [Equipment::ChestPressMachine];
    }

    #[DataProvider('unloaded')]
    public function testSomeEquipmentNeedsNoWeight(Equipment $equipment): void
    {
        self::assertFalse($equipment->carriesLoad());
    }

    /**
     * @return iterable<string, array{Equipment}>
     */
    public static function unloaded(): iterable
    {
        yield 'a pull-up bar' => [Equipment::PullUpBar];
        yield 'rings' => [Equipment::Rings];
        yield 'a dip station' => [Equipment::DipStation];
        yield 'a suspension trainer' => [Equipment::SuspensionTrainer];
        yield 'a resistance band' => [Equipment::ResistanceBand];
        yield 'a treadmill' => [Equipment::Treadmill];
        yield 'a jump rope' => [Equipment::JumpRope];
    }
}
