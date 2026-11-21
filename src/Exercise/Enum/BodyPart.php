<?php

declare(strict_types=1);

namespace App\Exercise\Enum;

enum BodyPart: string
{
    case Back = 'back';
    case Chest = 'chest';
    case Core = 'core';
    case FullBody = 'full_body';
    case LowerArms = 'lower_arms';
    case LowerLegs = 'lower_legs';
    case Shoulders = 'shoulders';
    case UpperArms = 'upper_arms';
    case UpperLegs = 'upper_legs';
}
