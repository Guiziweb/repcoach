<?php

declare(strict_types=1);

namespace App\Session;

enum SessionStatus: string
{
    case Running = 'running';
    case Paused = 'paused';
    case Finished = 'finished';
    case Aborted = 'aborted';

    public function isRunning(): bool
    {
        return self::Running === $this;
    }

    public function isOpen(): bool
    {
        return self::Running === $this || self::Paused === $this;
    }

    /**
     * @return list<self>
     */
    public static function openCases(): array
    {
        return array_values(array_filter(self::cases(), static fn (self $status): bool => $status->isOpen()));
    }
}
