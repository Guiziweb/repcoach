<?php

declare(strict_types=1);

namespace App\Session;

use App\Exercise\Exercise;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PerformedSetRepository::class)]
class PerformedSet
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    public function __construct(
        #[ORM\ManyToOne(targetEntity: WorkoutSession::class, inversedBy: 'sets')]
        #[ORM\JoinColumn(nullable: false)]
        public readonly WorkoutSession $session,
        #[ORM\ManyToOne(targetEntity: Exercise::class)]
        #[ORM\JoinColumn(nullable: false)]
        public readonly Exercise $exercise,
        #[ORM\ManyToOne(targetEntity: SessionExercise::class)]
        public readonly ?SessionExercise $planned,
        #[ORM\Column]
        public private(set) DateTimeImmutable $loggedAt,
        #[ORM\Column(nullable: true)]
        public private(set) ?int $reps = null,
        #[ORM\Column(nullable: true)]
        public private(set) ?int $seconds = null,
        #[ORM\Column(nullable: true)]
        public private(set) ?float $weight = null,
    ) {
        $session->record($this);
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function change(?int $reps, ?int $seconds, ?float $weight): void
    {
        $this->reps = $reps;
        $this->seconds = $seconds;
        $this->weight = $weight;
    }

    public function moveBy(int $seconds, ?DateTimeImmutable $notAfter): void
    {
        $moved = $this->loggedAt->modify(\sprintf('%+d seconds', $seconds));
        $this->loggedAt = null !== $notAfter && $moved > $notAfter ? $notAfter : $moved;
    }

    public function volume(): float
    {
        return (float) ($this->reps ?? 0) * ($this->weight ?? 0.0);
    }
}
