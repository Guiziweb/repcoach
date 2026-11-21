<?php

declare(strict_types=1);

namespace App\Session;

final readonly class SessionTally
{
    public function __construct(
        public WorkoutSession $session,
        public int $sets,
        public float $volume,
        public int $reps,
        public int $timedSeconds,
    ) {
    }
}
