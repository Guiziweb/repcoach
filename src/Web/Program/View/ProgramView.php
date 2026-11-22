<?php

declare(strict_types=1);

namespace App\Web\Program\View;

use App\Workout\Workout;

final readonly class ProgramView
{
    public function __construct(
        public Workout $workout,
        public int $position,
        public int $count,
        public Workout $previous,
        public Workout $next,
    ) {
    }
}
