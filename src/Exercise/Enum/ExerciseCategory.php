<?php

declare(strict_types=1);

namespace App\Exercise\Enum;

enum ExerciseCategory: string
{
    case Cardio = 'cardio';
    case Olympic = 'olympic';
    case Plyometrics = 'plyometrics';
    case Strength = 'strength';
    case Stretching = 'stretching';
}
