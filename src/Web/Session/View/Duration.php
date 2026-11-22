<?php

declare(strict_types=1);

namespace App\Web\Session\View;

final readonly class Duration
{
    public function __construct(
        public int $seconds,
    ) {
    }

    public function minutes(): int
    {
        return (int) round($this->seconds / 60);
    }

    public function clock(): string
    {
        $hours = intdiv($this->seconds, 3600);
        $rest = \sprintf('%02d:%02d', intdiv($this->seconds, 60) % 60, $this->seconds % 60);

        return $hours > 0 ? $hours.':'.$rest : $rest;
    }
}
