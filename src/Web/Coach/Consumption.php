<?php

declare(strict_types=1);

namespace App\Web\Coach;

final readonly class Consumption
{
    public function __construct(
        public int $context,
        public int $reply,
        public int $cached,
        public int $conversation,
        public int $memory,
    ) {
    }
}
