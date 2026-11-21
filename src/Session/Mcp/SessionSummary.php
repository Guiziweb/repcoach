<?php

declare(strict_types=1);

namespace App\Session\Mcp;

use App\Session\SessionStatus;
use App\Session\WorkoutSession;
use DateTimeImmutable;

final readonly class SessionSummary
{
    public const array SCHEMA = [
        'type' => 'object',
        'required' => ['id', 'workout', 'startedAt', 'status', 'durationSeconds', 'volume', 'reps', 'timedSeconds', 'sets', 'planned'],
        'properties' => [
            'id' => ['type' => 'integer'],
            'workout' => ['type' => 'string'],
            'startedAt' => ['type' => 'string'],
            'status' => ['type' => 'string', 'enum' => [SessionStatus::Running->value, SessionStatus::Paused->value, SessionStatus::Finished->value, SessionStatus::Aborted->value, 'none']],
            'durationSeconds' => ['type' => 'integer', 'description' => 'Seconds spent training, pauses excluded'],
            'volume' => ['type' => 'number', 'description' => 'Kilograms lifted: repetitions times weight, summed over the sets. Bodyweight sets add nothing here, see reps and timedSeconds'],
            'reps' => ['type' => 'integer', 'description' => 'Repetitions summed over the sets, bodyweight ones included'],
            'timedSeconds' => ['type' => 'integer', 'description' => 'Seconds of timed sets (planks, holds) summed over the sets'],
            'sets' => ['type' => 'array', 'items' => PerformedSetView::SCHEMA],
            'planned' => ['type' => 'array', 'items' => SessionPlanView::SCHEMA],
        ],
    ];

    /**
     * @param list<PerformedSetView> $sets
     * @param list<SessionPlanView>  $planned
     */
    private function __construct(
        public int $id,
        public string $workout,
        public string $startedAt,
        public string $status,
        public int $durationSeconds,
        public float $volume,
        public int $reps,
        public int $timedSeconds,
        public array $sets,
        public array $planned,
    ) {
    }

    public static function none(): self
    {
        return new self(0, '', '', 'none', 0, 0.0, 0, 0, [], []);
    }

    public static function of(WorkoutSession $session, DateTimeImmutable $now): self
    {
        $sets = [];
        $done = [];

        foreach ($session->getSets() as $set) {
            $sets[] = PerformedSetView::of($set);

            if (null !== $set->planned) {
                $line = spl_object_id($set->planned);
                $done[$line] = ($done[$line] ?? 0) + 1;
            }
        }

        $planned = [];

        foreach ($session->getPlan() as $line) {
            $planned[] = SessionPlanView::of($line, $done[spl_object_id($line)] ?? 0);
        }

        return new self(
            $session->getId() ?? 0,
            $session->workoutName,
            $session->startedAt->format(\DATE_ATOM),
            $session->getStatus()->value,
            $session->durationInSeconds($now),
            $session->volume(),
            $session->reps(),
            $session->timedSeconds(),
            $sets,
            $planned,
        );
    }
}
