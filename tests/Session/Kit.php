<?php

declare(strict_types=1);

namespace App\Tests\Session;

use App\Exercise\ExerciseRepository;
use App\Session\Action\FinishSession;
use App\Session\Action\LogSet;
use App\Session\Action\PauseSession;
use App\Session\Action\ResumeSession;
use App\Session\Action\StartSession;
use App\Session\Mcp\CurrentSessionTool;
use App\Session\Mcp\FinishSessionTool;
use App\Session\Mcp\LogSetTool;
use App\Session\Mcp\PauseSessionTool;
use App\Session\Mcp\ResumeSessionTool;
use App\Session\Mcp\StartSessionTool;
use App\Session\WorkoutSessionRepository;
use App\Tests\Checker;
use App\Workout\WorkoutRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Clock\ClockInterface;
use Symfony\Component\Workflow\WorkflowInterface;

final readonly class Kit
{
    public StartSessionTool $start;

    public LogSetTool $logSet;

    public CurrentSessionTool $current;

    public PauseSessionTool $pause;

    public ResumeSessionTool $resume;

    public FinishSessionTool $finish;

    public function __construct(
        EntityManagerInterface $entityManager,
        WorkoutSessionRepository $sessions,
        WorkoutRepository $workouts,
        ExerciseRepository $exercises,
        ClockInterface $clock,
        WorkflowInterface $workflow,
    ) {
        $check = Checker::make();

        $this->start = new StartSessionTool(new StartSession($entityManager, $sessions, $workouts, $clock), $clock);
        $this->logSet = new LogSetTool(new LogSet($entityManager, $sessions, $exercises, $clock, $check), $clock);
        $this->current = new CurrentSessionTool($sessions, $clock);
        $this->pause = new PauseSessionTool(new PauseSession($entityManager, $sessions, $clock, $workflow), $clock);
        $this->resume = new ResumeSessionTool(new ResumeSession($entityManager, $sessions, $clock, $workflow), $clock);
        $this->finish = new FinishSessionTool(new FinishSession($entityManager, $sessions, $clock, $workflow, $check), $clock);
    }
}
