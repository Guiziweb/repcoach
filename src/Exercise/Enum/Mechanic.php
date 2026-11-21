<?php

declare(strict_types=1);

namespace App\Exercise\Enum;

enum Mechanic: string
{
    case Compound = 'compound';
    case Isolation = 'isolation';
}
