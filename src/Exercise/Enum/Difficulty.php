<?php

declare(strict_types=1);

namespace App\Exercise\Enum;

enum Difficulty: string
{
    case Beginner = 'beginner';
    case Intermediate = 'intermediate';
    case Advanced = 'advanced';
}
