<?php

declare(strict_types=1);

namespace App\Workout;

use App\Shared\Limits;
use App\Shared\Refused;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: WorkoutRepository::class)]
#[ORM\UniqueConstraint(fields: ['name'])]
class Workout
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    /** @var Collection<int, WorkoutExercise> */
    #[ORM\OneToMany(targetEntity: WorkoutExercise::class, mappedBy: 'workout', cascade: ['persist'], orphanRemoval: true)]
    #[ORM\OrderBy(['position' => 'ASC'])]
    private Collection $exercises;

    public function __construct(
        #[ORM\Column(length: Limits::MAX_NAME)]
        public private(set) string $name,
    ) {
        $this->exercises = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    /** @return Collection<int, WorkoutExercise> */
    public function getExercises(): Collection
    {
        return $this->exercises;
    }

    public function plan(WorkoutExercise $exercise): void
    {
        if (!$this->exercises->contains($exercise)) {
            $this->exercises->add($exercise);
        }
    }

    public function line(int $position): WorkoutExercise
    {
        foreach ($this->exercises as $exercise) {
            if ($exercise->position === $position) {
                return $exercise;
            }
        }

        throw new Refused(\sprintf('"%s" has no exercise at position %d. It has %d.', $this->name, $position, $this->exercises->count()));
    }

    public function nextPosition(): int
    {
        $last = 0;

        foreach ($this->exercises as $exercise) {
            $last = max($last, $exercise->position);
        }

        return $last + 1;
    }

    public function rename(string $name): void
    {
        $this->name = $name;
    }

    public function move(WorkoutExercise $exercise, int $to): void
    {
        $ordered = $this->exercises->toArray();
        usort($ordered, static fn (WorkoutExercise $a, WorkoutExercise $b): int => $a->position <=> $b->position);

        $from = array_search($exercise, $ordered, true);

        if (false === $from) {
            return;
        }

        array_splice($ordered, $from, 1);
        array_splice($ordered, max(0, min($to, \count($ordered) + 1) - 1), 0, [$exercise]);

        $position = 1;

        foreach ($ordered as $line) {
            $line->moveTo($position++);
        }
    }

    public function remove(WorkoutExercise $exercise): void
    {
        $this->exercises->removeElement($exercise);
        $this->renumber();
    }

    private function renumber(): void
    {
        $position = 1;

        foreach ($this->exercises as $exercise) {
            $exercise->moveTo($position++);
        }
    }
}
