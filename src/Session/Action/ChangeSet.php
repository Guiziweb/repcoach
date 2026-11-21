<?php

declare(strict_types=1);

namespace App\Session\Action;

use App\Session\PerformedSetRepository;
use App\Session\WorkoutSession;
use App\Shared\Check;
use App\Shared\ExerciseEffort;
use App\Shared\Refused;
use Doctrine\ORM\EntityManagerInterface;

final readonly class ChangeSet
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private PerformedSetRepository $sets,
        private Check $check,
    ) {
    }

    public function __invoke(int $set, ?int $reps, ?int $seconds, ?float $weight): WorkoutSession
    {
        if (null === $reps && null === $seconds && null === $weight) {
            throw new Refused('Give the repetitions, the seconds or the weight to change, there is nothing to change otherwise.');
        }

        $found = $this->sets->get($set);
        $replacesEffort = null !== $reps || null !== $seconds;
        $carriesLoad = $found->exercise->carriesLoad();
        $unloaded = !$carriesLoad && 0.0 === $weight;
        $effort = new ExerciseEffort(
            $replacesEffort ? $reps : $found->reps,
            $replacesEffort ? $seconds : $found->seconds,
            $unloaded ? null : ($weight ?? $found->weight),
            $carriesLoad,
        );
        ($this->check)($effort);
        $found->change($effort->reps, $effort->seconds, $effort->weight);
        $this->entityManager->flush();

        return $found->session;
    }
}
