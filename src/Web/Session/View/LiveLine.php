<?php

declare(strict_types=1);

namespace App\Web\Session\View;

use App\Session\PerformedSet;
use App\Session\SessionExercise;

final readonly class LiveLine
{
    /**
     * @param list<PerformedSet> $done
     */
    public function __construct(
        public SessionExercise $planned,
        public array $done,
    ) {
    }

    public function remaining(): int
    {
        return max(0, $this->planned->sets - \count($this->done));
    }
}
