<?php

declare(strict_types=1);

namespace App\Shared;

use Symfony\Component\Validator\Constraints as Assert;

readonly class Effort
{
    public function __construct(
        #[Assert\Range(min: 1, max: Limits::MAX_REPS, notInRangeMessage: 'Repetitions must be between {{ min }} and {{ max }}.')]
        public ?int $reps,
        #[Assert\Range(min: 1, max: Limits::MAX_SECONDS, notInRangeMessage: 'Seconds must be between {{ min }} and {{ max }}.')]
        public ?int $seconds,
        #[Assert\Range(min: 0, max: Limits::MAX_WEIGHT, notInRangeMessage: 'The weight must be between {{ min }} and {{ max }} kilograms.')]
        public ?float $weight,
    ) {
    }

    #[Assert\IsTrue(message: 'Give either repetitions or a duration.')]
    public function isEitherGiven(): bool
    {
        return null !== $this->reps || null !== $this->seconds;
    }

    #[Assert\IsTrue(message: 'Give repetitions or a duration, not both.')]
    public function isNotBoth(): bool
    {
        return null === $this->reps || null === $this->seconds;
    }
}
