<?php

declare(strict_types=1);

namespace App\Shared;

enum Reason
{
    case WorkoutNotFound;
    case SessionNotFound;
    case SetNotFound;
    case ExerciseNotFound;
    case NoOpenSession;
    case SessionAlreadyOpen;
    case SessionPaused;
    case SessionStillOpenForCorrection;
    case SessionStillOpenForDeletion;
}
