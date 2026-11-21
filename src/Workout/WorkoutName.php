<?php

declare(strict_types=1);

namespace App\Workout;

use App\Shared\Limits;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class WorkoutName
{
    #[Assert\NotBlank(message: 'A workout needs a name.')]
    #[Assert\Length(max: Limits::MAX_NAME, maxMessage: 'A workout name has at most {{ limit }} characters.')]
    public string $value;

    public function __construct(string $name)
    {
        $this->value = trim($name);
    }
}
