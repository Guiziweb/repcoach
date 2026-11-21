<?php

declare(strict_types=1);

namespace App\Exercise;

use App\Exercise\Enum\BodyPart;
use App\Exercise\Enum\Difficulty;
use App\Exercise\Enum\Equipment;
use App\Exercise\Enum\ExerciseCategory;
use App\Exercise\Enum\ForceType;
use App\Exercise\Enum\Mechanic;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ExerciseRepository::class)]
class Exercise
{
    /**
     * @param list<string>                         $primaryMuscles
     * @param list<string>                         $secondaryMuscles
     * @param list<string>                         $instructions
     * @param list<string>                         $tags
     * @param list<string>                         $goals
     * @param array<string, array<string, string>> $images
     */
    public function __construct(
        #[ORM\Id]
        #[ORM\Column(length: 60)]
        public readonly string $id,
        #[ORM\Column(length: 60)]
        public string $name,
        #[ORM\Column(type: Types::TEXT)]
        public string $description,
        #[ORM\Column(enumType: ExerciseCategory::class)]
        public ExerciseCategory $category,
        #[ORM\Column(enumType: BodyPart::class)]
        public BodyPart $bodyPart,
        #[ORM\Column(enumType: ForceType::class)]
        public ForceType $forceType,
        #[ORM\Column(enumType: Mechanic::class)]
        public Mechanic $mechanic,
        #[ORM\Column(enumType: Difficulty::class)]
        public Difficulty $difficulty,
        #[ORM\Column(type: Types::JSON)]
        public array $primaryMuscles,
        #[ORM\Column(type: Types::JSON)]
        public array $secondaryMuscles,
        #[ORM\Column(type: Types::JSON)]
        public array $instructions,
        #[ORM\Column(type: Types::JSON)]
        public array $tags,
        #[ORM\Column(type: Types::JSON)]
        public array $goals,
        #[ORM\Column(type: Types::JSON)]
        public array $images,
        #[ORM\Column]
        public bool $bodyweight,
        #[ORM\Column]
        public bool $unilateral,
        #[ORM\Column]
        public float $met,
        #[ORM\Column(nullable: true, enumType: Equipment::class)]
        public ?Equipment $equipment = null,
    ) {
    }

    public function carriesLoad(): bool
    {
        return $this->equipment?->carriesLoad() ?? false;
    }

    public function refreshFrom(self $fresh): void
    {
        $this->name = $fresh->name;
        $this->description = $fresh->description;
        $this->category = $fresh->category;
        $this->bodyPart = $fresh->bodyPart;
        $this->forceType = $fresh->forceType;
        $this->mechanic = $fresh->mechanic;
        $this->difficulty = $fresh->difficulty;
        $this->primaryMuscles = $fresh->primaryMuscles;
        $this->secondaryMuscles = $fresh->secondaryMuscles;
        $this->instructions = $fresh->instructions;
        $this->tags = $fresh->tags;
        $this->goals = $fresh->goals;
        $this->images = $fresh->images;
        $this->bodyweight = $fresh->bodyweight;
        $this->unilateral = $fresh->unilateral;
        $this->met = $fresh->met;
        $this->equipment = $fresh->equipment;
    }
}
