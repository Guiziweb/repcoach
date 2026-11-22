<?php

declare(strict_types=1);

namespace App\Web\Coach\Screen;

final class EffortInWords
{
    public static function of(?int $reps, ?int $seconds, ?float $weight): string
    {
        $amount = null !== $reps ? \sprintf('%d reps', $reps) : \sprintf('%d seconds', $seconds ?? 0);

        return null !== $weight ? \sprintf('%s at %s kg', $amount, $weight) : $amount;
    }
}
