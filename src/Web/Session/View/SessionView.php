<?php

declare(strict_types=1);

namespace App\Web\Session\View;

use App\Session\PerformedSet;
use App\Session\WorkoutSession;

final readonly class SessionView
{
    /**
     * @param array<string, list<PerformedSet>> $exercises
     */
    public function __construct(
        public WorkoutSession $session,
        public array $exercises,
        public Duration $duration,
        public ?WorkoutSession $previous,
        public ?WorkoutSession $next,
        public int $listPage,
    ) {
    }

    public static function of(WorkoutSession $session, Duration $duration, ?WorkoutSession $previous, ?WorkoutSession $next, int $listPage): self
    {
        $exercises = [];

        foreach ($session->getSets() as $set) {
            $exercises[$set->exercise->name][] = $set;
        }

        return new self($session, $exercises, $duration, $previous, $next, $listPage);
    }
}
