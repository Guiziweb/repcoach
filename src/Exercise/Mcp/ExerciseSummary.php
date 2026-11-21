<?php

declare(strict_types=1);

namespace App\Exercise\Mcp;

use App\Exercise\Exercise;

final readonly class ExerciseSummary
{
    public const array SCHEMA = [
        'type' => 'object',
        'required' => ['id', 'name', 'bodyPart', 'primaryMuscles', 'secondaryMuscles', 'tags', 'bodyweight', 'equipment'],
        'properties' => [
            'id' => ['type' => 'string'],
            'name' => ['type' => 'string'],
            'bodyPart' => ['type' => 'string'],
            'primaryMuscles' => ['type' => 'array', 'items' => ['type' => 'string']],
            'secondaryMuscles' => ['type' => 'array', 'items' => ['type' => 'string']],
            'tags' => ['type' => 'array', 'items' => ['type' => 'string']],
            'bodyweight' => ['type' => 'boolean'],
            'equipment' => ['type' => ['string', 'null']],
        ],
    ];

    /**
     * @param list<string> $primaryMuscles
     * @param list<string> $secondaryMuscles
     * @param list<string> $tags
     */
    public function __construct(
        public string $id,
        public string $name,
        public string $bodyPart,
        public array $primaryMuscles,
        public array $secondaryMuscles,
        public array $tags,
        public bool $bodyweight,
        public ?string $equipment,
    ) {
    }

    public static function of(Exercise $exercise): self
    {
        return new self(
            $exercise->id,
            $exercise->name,
            $exercise->bodyPart->value,
            $exercise->primaryMuscles,
            $exercise->secondaryMuscles,
            $exercise->tags,
            $exercise->bodyweight,
            $exercise->equipment?->value,
        );
    }
}
