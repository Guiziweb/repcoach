<?php

declare(strict_types=1);

namespace App\Exercise\Import;

use App\Exercise\Enum\BodyPart;
use App\Exercise\Enum\Difficulty;
use App\Exercise\Enum\Equipment;
use App\Exercise\Enum\ExerciseCategory;
use App\Exercise\Enum\ForceType;
use App\Exercise\Enum\Mechanic;
use App\Exercise\Exercise;
use InvalidArgumentException;
use ValueError;

final class ExerciseFactory
{
    /**
     * @param array<mixed, mixed> $row
     */
    public function fromRepDb(array $row): Exercise
    {
        try {
            return $this->build($row);
        } catch (ValueError|InvalidArgumentException $unknown) {
            throw new InvalidExerciseRow(\sprintf('Exercise "%s": %s', \is_string($row['id'] ?? null) ? $row['id'] : '?', $unknown->getMessage()), previous: $unknown);
        }
    }

    /**
     * @param array<mixed, mixed> $row
     */
    private function build(array $row): Exercise
    {
        return new Exercise(
            id: $this->text($row, 'id'),
            name: $this->text($row, 'name_en'),
            description: $this->text($row, 'description_en'),
            category: ExerciseCategory::from($this->text($row, 'category')),
            bodyPart: BodyPart::from($this->text($row, 'body_part')),
            forceType: ForceType::from($this->text($row, 'force_type')),
            mechanic: Mechanic::from($this->text($row, 'mechanic')),
            difficulty: Difficulty::from($this->text($row, 'difficulty')),
            primaryMuscles: $this->strings($row, 'primary_muscles'),
            secondaryMuscles: $this->strings($row, 'secondary_muscles'),
            instructions: $this->strings($row, 'instructions_en'),
            tags: $this->strings($row, 'tags'),
            goals: $this->strings($row, 'goals'),
            images: $this->images($row),
            bodyweight: (bool) ($row['is_bodyweight'] ?? false),
            unilateral: (bool) ($row['is_unilateral'] ?? false),
            met: $this->number($row, 'met'),
            equipment: $this->equipment($row),
        );
    }

    /**
     * @param array<mixed, mixed> $row
     */
    private function equipment(array $row): ?Equipment
    {
        $value = $row['equipment'] ?? null;

        if (null === $value) {
            return null;
        }

        if (!\is_string($value)) {
            throw new InvalidArgumentException('invalid "equipment"');
        }

        return Equipment::from($value);
    }

    /**
     * @param array<mixed, mixed> $row
     */
    private function text(array $row, string $key): string
    {
        $value = $row[$key] ?? null;

        if (!\is_string($value) || '' === $value) {
            throw new InvalidArgumentException(\sprintf('missing "%s"', $key));
        }

        return $value;
    }

    /**
     * @param array<mixed, mixed> $row
     */
    private function number(array $row, string $key): float
    {
        $value = $row[$key] ?? null;

        return \is_int($value) || \is_float($value) ? (float) $value : 0.0;
    }

    /**
     * @param array<mixed, mixed> $row
     *
     * @return list<string>
     */
    private function strings(array $row, string $key): array
    {
        $values = $row[$key] ?? [];

        if (!\is_array($values)) {
            return [];
        }

        return array_values(array_filter($values, \is_string(...)));
    }

    /**
     * @param array<mixed, mixed> $row
     *
     * @return array<string, array<string, string>>
     */
    private function images(array $row): array
    {
        $images = $row['images'] ?? [];
        $mapped = [];

        if (!\is_array($images)) {
            return [];
        }

        foreach ($images as $style => $variants) {
            if (!\is_string($style) || !\is_array($variants)) {
                continue;
            }

            foreach ($variants as $variant => $path) {
                if (\is_string($variant) && \is_string($path)) {
                    $mapped[$style][$variant] = $path;
                }
            }
        }

        return $mapped;
    }
}
