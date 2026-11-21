<?php

declare(strict_types=1);

namespace App\Session\Action;

use App\Exercise\Exercise;
use App\Exercise\ExerciseRepository;
use App\Session\PerformedSet;
use App\Session\SessionExercise;
use App\Session\WorkoutSession;
use App\Session\WorkoutSessionRepository;
use App\Shared\Check;
use App\Shared\ExerciseEffort;
use App\Shared\Reason;
use App\Shared\Refused;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Clock\ClockInterface;

final readonly class LogSet
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private WorkoutSessionRepository $sessions,
        private ExerciseRepository $exercises,
        private ClockInterface $clock,
        private Check $check,
    ) {
    }

    public function __invoke(?int $position, ?string $exercise, ?int $reps, ?int $seconds, ?float $weight): WorkoutSession
    {
        $session = $this->sessions->getOpen();

        if (!$session->getStatus()->isRunning()) {
            throw new Refused('The session is paused.', Reason::SessionPaused);
        }

        $line = null === $position ? null : $session->line($position);

        if ($line instanceof SessionExercise) {
            if (null !== $exercise && $line->exercise->id !== $exercise) {
                throw new Refused(\sprintf('Position %d is %s (%s), not "%s". Give the position or the exercise id, not a mismatch.', $position, $line->exercise->name, $line->exercise->id, $exercise));
            }

            $effort = $line->effortFor($reps, $seconds, $weight);
            ($this->check)($effort);
            $line->perform($effort, $this->clock->now());
        } else {
            $done = $this->exercise($exercise);
            $effort = new ExerciseEffort($reps, $seconds, $weight, $done->carriesLoad());
            ($this->check)($effort);
            new PerformedSet($session, $done, null, $this->clock->now(), $effort->reps, $effort->seconds, $effort->weight);
        }

        $this->entityManager->flush();

        return $session;
    }

    private function exercise(?string $id): Exercise
    {
        $found = null === $id ? null : $this->exercises->find($id);

        return $found ?? throw new Refused(\sprintf('Give a position from the plan, or an exercise id for a set outside it. "%s" is neither.', $id ?? ''), Reason::ExerciseNotFound);
    }
}
