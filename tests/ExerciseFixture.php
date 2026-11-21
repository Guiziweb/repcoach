<?php

declare(strict_types=1);

namespace App\Tests;

use App\Exercise\Enum\BodyPart;
use App\Exercise\Enum\Difficulty;
use App\Exercise\Enum\Equipment;
use App\Exercise\Enum\ExerciseCategory;
use App\Exercise\Enum\ForceType;
use App\Exercise\Enum\Mechanic;
use App\Exercise\Exercise;

final class ExerciseFixture
{
    /**
     * @param list<string> $primaryMuscles
     * @param list<string> $tags
     */
    public static function exercise(
        string $id = 'bench-press',
        string $name = 'Barbell Bench Press',
        BodyPart $bodyPart = BodyPart::Chest,
        array $primaryMuscles = ['pectoralis_major'],
        bool $bodyweight = false,
        array $tags = [],
        ?Equipment $equipment = null,
    ): Exercise {
        return new Exercise(
            id: $id,
            name: $name,
            description: 'Description.',
            category: ExerciseCategory::Strength,
            bodyPart: $bodyPart,
            forceType: ForceType::Push,
            mechanic: Mechanic::Compound,
            difficulty: Difficulty::Intermediate,
            primaryMuscles: $primaryMuscles,
            secondaryMuscles: [],
            instructions: ['Do it.'],
            tags: $tags,
            goals: [],
            images: [],
            bodyweight: $bodyweight,
            unilateral: false,
            met: 6.0,
            equipment: $equipment ?? ($bodyweight ? null : Equipment::Barbell),
        );
    }
}
