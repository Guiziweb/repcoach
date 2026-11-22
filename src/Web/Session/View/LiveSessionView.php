<?php

declare(strict_types=1);

namespace App\Web\Session\View;

use App\Session\PerformedSet;
use App\Session\WorkoutSession;

final readonly class LiveSessionView
{
    /**
     * @param list<LiveLine>                    $lines
     * @param array<string, list<PerformedSet>> $extras
     */
    public function __construct(
        public WorkoutSession $session,
        public array $lines,
        public array $extras,
        public Duration $duration,
    ) {
    }

    public static function of(WorkoutSession $session, Duration $duration): self
    {
        $sets = $session->getSets()->toArray();
        $lines = [];
        $extras = [];

        foreach ($session->getPlan() as $planned) {
            $lines[] = new LiveLine($planned, array_values(array_filter($sets, static fn (PerformedSet $set): bool => $set->planned === $planned)));
        }

        foreach ($sets as $set) {
            if (null === $set->planned) {
                $extras[$set->exercise->name][] = $set;
            }
        }

        return new self($session, $lines, $extras, $duration);
    }
}
