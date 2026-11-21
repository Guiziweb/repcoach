<?php

declare(strict_types=1);

namespace App\Session;

use App\Exercise\Exercise;
use App\Shared\Effort;
use App\Shared\ExerciseEffort;
use App\Workout\WorkoutExercise;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class SessionExercise
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    public function __construct(
        #[ORM\ManyToOne(targetEntity: WorkoutSession::class, inversedBy: 'plan')]
        #[ORM\JoinColumn(nullable: false)]
        public private(set) WorkoutSession $session,
        #[ORM\ManyToOne(targetEntity: Exercise::class)]
        #[ORM\JoinColumn(nullable: false)]
        public private(set) Exercise $exercise,
        #[ORM\Column]
        public private(set) int $position,
        #[ORM\Column]
        public private(set) int $sets,
        #[ORM\Column(nullable: true)]
        public private(set) ?int $reps = null,
        #[ORM\Column(nullable: true)]
        public private(set) ?int $seconds = null,
        #[ORM\Column(nullable: true)]
        public private(set) ?float $weight = null,
    ) {
        $session->plan($this);
    }

    public static function copyOf(WorkoutExercise $planned, WorkoutSession $session): self
    {
        return new self(
            $session,
            $planned->exercise,
            $planned->position,
            $planned->sets,
            $planned->reps,
            $planned->seconds,
            $planned->weight,
        );
    }

    public function effortFor(?int $reps, ?int $seconds, ?float $weight): ExerciseEffort
    {
        $planned = null === $reps && null === $seconds;

        return new ExerciseEffort($planned ? $this->reps : $reps, $planned ? $this->seconds : $seconds, $weight ?? $this->weight, $this->exercise->carriesLoad());
    }

    public function perform(Effort $effort, DateTimeImmutable $at): PerformedSet
    {
        return new PerformedSet($this->session, $this->exercise, $this, $at, $effort->reps, $effort->seconds, $effort->weight);
    }

    public function getId(): ?int
    {
        return $this->id;
    }
}
