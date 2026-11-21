<?php

declare(strict_types=1);

namespace App\Workout;

use App\Exercise\Exercise;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class WorkoutExercise
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    public function __construct(
        #[ORM\ManyToOne(targetEntity: Workout::class, inversedBy: 'exercises')]
        #[ORM\JoinColumn(nullable: false)]
        public readonly Workout $workout,
        #[ORM\ManyToOne(targetEntity: Exercise::class)]
        #[ORM\JoinColumn(nullable: false)]
        public readonly Exercise $exercise,
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
        $workout->plan($this);
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function change(int $sets, ?int $reps, ?int $seconds, ?float $weight): void
    {
        $this->sets = $sets;
        $this->reps = $reps;
        $this->seconds = $seconds;
        $this->weight = $weight;
    }

    public function moveTo(int $position): void
    {
        $this->position = $position;
    }
}
