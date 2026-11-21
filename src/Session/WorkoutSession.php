<?php

declare(strict_types=1);

namespace App\Session;

use App\Shared\Reason;
use App\Shared\Refused;
use App\Workout\Workout;
use DateTimeImmutable;
use DateTimeZone;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use LogicException;

#[ORM\Entity(repositoryClass: WorkoutSessionRepository::class)]
class WorkoutSession
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(enumType: SessionStatus::class)]
    private SessionStatus $status = SessionStatus::Running;

    #[ORM\Column(nullable: true)]
    private ?DateTimeImmutable $endedAt = null;

    #[ORM\Column(nullable: true)]
    private ?DateTimeImmutable $pausedAt = null;

    #[ORM\Column]
    private int $pausedSeconds = 0;

    /** @var Collection<int, SessionExercise> */
    #[ORM\OneToMany(targetEntity: SessionExercise::class, mappedBy: 'session', cascade: ['persist'], orphanRemoval: true)]
    #[ORM\OrderBy(['position' => 'ASC'])]
    private Collection $plan;

    /** @var Collection<int, PerformedSet> */
    #[ORM\OneToMany(targetEntity: PerformedSet::class, mappedBy: 'session', cascade: ['persist'], orphanRemoval: true)]
    #[ORM\OrderBy(['loggedAt' => 'ASC', 'id' => 'ASC'])]
    private Collection $sets;

    #[ORM\Column(length: 120)]
    public private(set) string $workoutName;

    #[ORM\ManyToOne(targetEntity: Workout::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    public private(set) ?Workout $workout;

    public function __construct(
        #[ORM\Column]
        public private(set) DateTimeImmutable $startedAt,
        Workout $workout,
    ) {
        $this->plan = new ArrayCollection();
        $this->sets = new ArrayCollection();
        $this->workout = $workout;
        $this->workoutName = $workout->name;

        foreach ($workout->getExercises() as $planned) {
            SessionExercise::copyOf($planned, $this);
        }
    }

    public function forget(): void
    {
        $this->workout = null;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getStatus(): SessionStatus
    {
        return $this->status;
    }

    public function getEndedAt(): ?DateTimeImmutable
    {
        return $this->endedAt;
    }

    /** @return Collection<int, SessionExercise> */
    public function getPlan(): Collection
    {
        return $this->plan;
    }

    /** @return Collection<int, PerformedSet> */
    public function getSets(): Collection
    {
        return $this->sets;
    }

    public function plan(SessionExercise $exercise): void
    {
        if (!$this->plan->contains($exercise)) {
            $this->plan->add($exercise);
        }
    }

    public function record(PerformedSet $set): void
    {
        if (SessionStatus::Paused === $this->status) {
            throw new SessionNotRunning('A paused session cannot record a set.', Reason::SessionPaused);
        }

        if (!$this->sets->contains($set)) {
            $this->sets->add($set);
        }
    }

    public function line(int $position): SessionExercise
    {
        foreach ($this->plan as $line) {
            if ($line->position === $position) {
                return $line;
            }
        }

        throw new Refused(\sprintf('That session has no line at position %d. It has %d.', $position, $this->plan->count()));
    }

    public function removeSet(PerformedSet $set): void
    {
        $this->sets->removeElement($set);
    }

    public function endIfRescheduled(?DateTimeImmutable $startedAt, ?int $lastedMinutes): ?DateTimeImmutable
    {
        if (null === $this->endedAt) {
            return null;
        }

        $start = ($startedAt ?? $this->startedAt)->setTimezone(new DateTimeZone(date_default_timezone_get()));
        $seconds = null === $lastedMinutes ? $this->endedAt->getTimestamp() - $this->startedAt->getTimestamp() : $lastedMinutes * 60 + $this->pausedSeconds;

        return $start->modify(\sprintf('+%d seconds', $seconds));
    }

    public function reschedule(?DateTimeImmutable $startedAt, ?int $lastedMinutes): void
    {
        $endedAt = $this->endIfRescheduled($startedAt, $lastedMinutes);

        $shift = 0;

        if (null !== $startedAt) {
            $shift = $startedAt->getTimestamp() - $this->startedAt->getTimestamp();
            $this->startedAt = $startedAt->setTimezone(new DateTimeZone(date_default_timezone_get()));
        }

        if (null !== $endedAt) {
            $this->endedAt = $endedAt;
        }

        foreach ($this->sets as $set) {
            $set->moveBy($shift, $this->endedAt);
        }
    }

    /**
     * @param array<string, mixed> $context
     */
    public function setStatus(SessionStatus $status, array $context = []): void
    {
        $at = $context['at'] ?? null;

        if (!$at instanceof DateTimeImmutable) {
            throw new LogicException('A status change needs the moment it happens.');
        }

        $lasted = $context['lasted'] ?? null;

        match ($status) {
            SessionStatus::Paused => $this->pausedAt = $at,
            SessionStatus::Running => $this->settlePause($at),
            SessionStatus::Finished => $this->conclude($at, \is_int($lasted) ? $lasted : null),
            SessionStatus::Aborted => $this->conclude($at, null),
        };

        $this->status = $status;
    }

    public function volume(): float
    {
        return array_sum($this->sets->map(static fn (PerformedSet $set): float => $set->volume())->toArray());
    }

    public function reps(): int
    {
        return array_sum($this->sets->map(static fn (PerformedSet $set): int => $set->reps ?? 0)->toArray());
    }

    public function timedSeconds(): int
    {
        return array_sum($this->sets->map(static fn (PerformedSet $set): int => $set->seconds ?? 0)->toArray());
    }

    public function durationInSeconds(DateTimeImmutable $now): int
    {
        $end = $this->endedAt ?? $now;
        $paused = $this->pausedSeconds;

        if (null !== $this->pausedAt) {
            $paused += $now->getTimestamp() - $this->pausedAt->getTimestamp();
        }

        return max(0, $end->getTimestamp() - $this->startedAt->getTimestamp() - $paused);
    }

    private function settlePause(DateTimeImmutable $at): void
    {
        if (null === $this->pausedAt) {
            return;
        }

        $this->pausedSeconds += $at->getTimestamp() - $this->pausedAt->getTimestamp();
        $this->pausedAt = null;
    }

    private function conclude(DateTimeImmutable $at, ?int $lastedMinutes): void
    {
        $this->settlePause($at);
        $this->endedAt = $at;

        if (null !== $lastedMinutes) {
            $this->startedAt = $at->modify(\sprintf('-%d seconds', $lastedMinutes * 60 + $this->pausedSeconds));
        }
    }
}
