<?php

declare(strict_types=1);

namespace App\Shared;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class ExerciseEffort extends Effort
{
    public function __construct(?int $reps, ?int $seconds, ?float $weight, private bool $carriesLoad)
    {
        parent::__construct($reps, $seconds, $weight);
    }

    #[Assert\IsTrue(message: 'Give the weight in kilograms: this exercise is lifted with a load.')]
    public function isWeighedWhenLoaded(): bool
    {
        return !$this->carriesLoad || null === $this->reps || null !== $this->weight || null !== $this->seconds;
    }
}
