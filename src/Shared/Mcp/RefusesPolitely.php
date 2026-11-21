<?php

declare(strict_types=1);

namespace App\Shared\Mcp;

use App\Shared\Reason;
use App\Shared\Refused;
use Mcp\Exception\ToolCallException;

trait RefusesPolitely
{
    /**
     * @template T
     *
     * @param callable(): T $use
     *
     * @return T
     */
    private function refusing(callable $use): mixed
    {
        try {
            return $use();
        } catch (Refused $refused) {
            throw new ToolCallException($refused->getMessage().self::hint($refused->reason), previous: $refused);
        }
    }

    private static function hint(?Reason $reason): string
    {
        return match ($reason) {
            Reason::WorkoutNotFound => ' Use list_workouts to see them.',
            Reason::SessionNotFound => ' Use list_sessions to see them.',
            Reason::SetNotFound => ' Use show_session to see the ids of the sets.',
            Reason::ExerciseNotFound => ' Use search_exercises to find its id.',
            Reason::NoOpenSession => ' Start one with start_session, or see the workouts with list_workouts.',
            Reason::SessionAlreadyOpen => ' Finish it with finish_session or abort it with abort_session first. current_session shows it.',
            Reason::SessionPaused => ' Resume it with resume_session before logging a set.',
            Reason::SessionStillOpenForCorrection => ' Finish it with finish_session first.',
            Reason::SessionStillOpenForDeletion => ' Abort it with abort_session first.',
            null => '',
        };
    }
}
