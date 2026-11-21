<?php

declare(strict_types=1);

namespace App\Tests\Session;

use App\Session\Action\StartSession;
use App\Session\Mcp\StartSessionTool;
use App\Session\WorkoutSessionRepository;
use App\Workout\WorkoutRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Clock\ClockInterface;

final readonly class Kit
{
    public StartSessionTool $start;

    public function __construct(
        EntityManagerInterface $entityManager,
        WorkoutSessionRepository $sessions,
        WorkoutRepository $workouts,
        ClockInterface $clock,
    ) {
        $this->start = new StartSessionTool(new StartSession($entityManager, $sessions, $workouts, $clock), $clock);
    }
}
