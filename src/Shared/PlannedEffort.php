<?php

declare(strict_types=1);

namespace App\Shared;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class PlannedEffort extends Effort
{
    public function __construct(
        #[Assert\Range(min: 1, max: Limits::MAX_SETS, notInRangeMessage: 'Sets must be between {{ min }} and {{ max }}.')]
        public int $sets,
        ?int $reps,
        ?int $seconds,
        ?float $weight,
    ) {
        parent::__construct($reps, $seconds, $weight);
    }
}
