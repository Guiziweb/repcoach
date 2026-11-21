<?php

declare(strict_types=1);

namespace App\Session\Mcp;

use App\Session\PerformedSet;

final readonly class PerformedSetView
{
    public const array SCHEMA = [
        'type' => 'object',
        'required' => ['id', 'exercise', 'reps', 'seconds', 'weight'],
        'properties' => [
            'id' => ['type' => 'integer'],
            'exercise' => ['type' => 'string'],
            'reps' => ['type' => ['integer', 'null']],
            'seconds' => ['type' => ['integer', 'null']],
            'weight' => ['type' => ['number', 'null']],
        ],
    ];

    private function __construct(
        public int $id,
        public string $exercise,
        public ?int $reps,
        public ?int $seconds,
        public ?float $weight,
    ) {
    }

    public static function of(PerformedSet $set): self
    {
        return new self($set->getId() ?? 0, $set->exercise->name, $set->reps, $set->seconds, $set->weight);
    }
}
