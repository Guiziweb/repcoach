<?php

declare(strict_types=1);

namespace App\Session\Mcp;

use App\Session\SessionStatus;
use App\Session\SessionTally;
use DateTimeImmutable;

final readonly class SessionOverview
{
    public const array SCHEMA = [
        'type' => 'object',
        'required' => ['id', 'workout', 'startedAt', 'status', 'durationSeconds', 'volume', 'sets'],
        'properties' => [
            'id' => ['type' => 'integer'],
            'workout' => ['type' => 'string'],
            'startedAt' => ['type' => 'string'],
            'status' => ['type' => 'string', 'enum' => [SessionStatus::Running->value, SessionStatus::Paused->value, SessionStatus::Finished->value, SessionStatus::Aborted->value]],
            'durationSeconds' => ['type' => 'integer', 'description' => 'Seconds spent training, pauses excluded'],
            'volume' => ['type' => 'number', 'description' => 'Kilograms lifted: repetitions times weight, summed over the sets'],
            'sets' => ['type' => 'integer'],
        ],
    ];

    private function __construct(
        public int $id,
        public string $workout,
        public string $startedAt,
        public string $status,
        public int $durationSeconds,
        public float $volume,
        public int $sets,
    ) {
    }

    public static function of(SessionTally $tally, DateTimeImmutable $now): self
    {
        $session = $tally->session;

        return new self(
            $session->getId() ?? 0,
            $session->workoutName,
            $session->startedAt->format(\DATE_ATOM),
            $session->getStatus()->value,
            $session->durationInSeconds($now),
            $tally->volume,
            $tally->sets,
        );
    }
}
