<?php

declare(strict_types=1);

namespace App\Exercise\Enum;

enum ForceType: string
{
    case Dynamic = 'dynamic';
    case Pull = 'pull';
    case Push = 'push';
    case Static = 'static';
}
