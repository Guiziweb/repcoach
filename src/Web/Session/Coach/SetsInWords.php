<?php

declare(strict_types=1);

namespace App\Web\Session\Coach;

use App\Session\PerformedSet;
use App\Web\Coach\Screen\EffortInWords;

final class SetsInWords
{
    /**
     * @param array<PerformedSet> $sets
     */
    public static function of(array $sets): string
    {
        if ([] === $sets) {
            return 'no set done';
        }

        return implode(', ', array_map(
            static fn (PerformedSet $set): string => \sprintf('set id %d: %s', $set->getId(), EffortInWords::of($set->reps, $set->seconds, $set->weight)),
            $sets,
        ));
    }
}
