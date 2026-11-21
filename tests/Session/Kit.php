<?php

declare(strict_types=1);

namespace App\Tests\Session;

use App\Exercise\ExerciseRepository;
use App\Session\Action\LogSet;
use App\Session\Action\StartSession;
use App\Session\Mcp\LogSetTool;
use App\Session\Mcp\StartSessionTool;
use App\Session\WorkoutSessionRepository;
use App\Tests\Checker;
use App\Workout\WorkoutRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Clock\ClockInterface;

final readonly class Kit
{
    public StartSessionTool $start;

    public LogSetTool $logSet;

    public function __construct(
        EntityManagerInterface $entityManager,
        WorkoutSessionRepository $sessions,
        WorkoutRepository $workouts,
        ExerciseRepository $exercises,
        ClockInterface $clock,
    ) {
        $check = Checker::make();

        $this->start = new StartSessionTool(new StartSession($entityManager, $sessions, $workouts, $clock), $clock);
        $this->logSet = new LogSetTool(new LogSet($entityManager, $sessions, $exercises, $clock, $check), $clock);
    }
}
