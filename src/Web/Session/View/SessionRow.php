<?php

declare(strict_types=1);

namespace App\Web\Session\View;

use App\Session\SessionTally;

final readonly class SessionRow
{
    public function __construct(
        public SessionTally $tally,
        public Duration $duration,
    ) {
    }
}
