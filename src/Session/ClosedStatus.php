<?php

declare(strict_types=1);

namespace App\Session;

enum ClosedStatus: string
{
    case Finished = 'finished';
    case Aborted = 'aborted';

    public function status(): SessionStatus
    {
        return match ($this) {
            self::Finished => SessionStatus::Finished,
            self::Aborted => SessionStatus::Aborted,
        };
    }
}
