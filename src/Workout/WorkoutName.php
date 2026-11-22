<?php

declare(strict_types=1);

namespace App\Workout;

use App\Shared\Limits;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class WorkoutName
{
    #[Assert\NotBlank(message: 'A workout needs a name.')]
    #[Assert\Length(max: Limits::MAX_NAME, maxMessage: 'A workout name has at most {{ limit }} characters.')]
    #[Assert\Regex(pattern: '/\p{Cc}/u', match: false, message: 'A workout name fits on one line, without tabs or line breaks.')]
    #[Assert\Regex(pattern: '#(^|/)\.{1,2}(/|$)#', match: false, message: 'A workout name cannot have "." or ".." between slashes.')]
    public string $value;

    public function __construct(string $name)
    {
        $this->value = trim($name);
    }
}
