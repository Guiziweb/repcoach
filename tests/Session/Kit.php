<?php

declare(strict_types=1);

namespace App\Tests\Session;

use App\Exercise\ExerciseRepository;
use App\Session\Action\AbortSession;
use App\Session\Action\AddSet;
use App\Session\Action\ChangeSet;
use App\Session\Action\DeleteSession;
use App\Session\Action\FinishSession;
use App\Session\Action\LogSet;
use App\Session\Action\PauseSession;
use App\Session\Action\RemoveSet;
use App\Session\Action\ResumeSession;
use App\Session\Action\StartSession;
use App\Session\Action\UpdateSession;
use App\Session\Mcp\AbortSessionTool;
use App\Session\Mcp\AddSetTool;
use App\Session\Mcp\ChangeSetTool;
use App\Session\Mcp\CurrentSessionTool;
use App\Session\Mcp\DeleteSessionTool;
use App\Session\Mcp\FinishSessionTool;
use App\Session\Mcp\ListSessionsTool;
use App\Session\Mcp\LogSetTool;
use App\Session\Mcp\PauseSessionTool;
use App\Session\Mcp\RemoveSetTool;
use App\Session\Mcp\ResumeSessionTool;
use App\Session\Mcp\ShowSessionTool;
use App\Session\Mcp\StartSessionTool;
use App\Session\Mcp\UpdateSessionTool;
use App\Session\PerformedSetRepository;
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

    public AbortSessionTool $abort;

    public FinishSessionTool $finish;

    public ListSessionsTool $listSessions;

    public ShowSessionTool $showSession;

    public UpdateSessionTool $updateSession;

    public ChangeSetTool $changeSet;

    public AddSetTool $addSet;

    public RemoveSetTool $removeSet;

    public DeleteSessionTool $deleteSession;

    public function __construct(
        EntityManagerInterface $entityManager,
        WorkoutSessionRepository $sessions,
        WorkoutRepository $workouts,
        PerformedSetRepository $sets,
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
        $this->abort = new AbortSessionTool(new AbortSession($entityManager, $sessions, $clock, $workflow), $clock);
        $this->finish = new FinishSessionTool(new FinishSession($entityManager, $sessions, $clock, $workflow, $check), $clock);
        $this->listSessions = new ListSessionsTool($sessions, $check, $clock);
        $this->showSession = new ShowSessionTool($sessions, $clock);
        $this->changeSet = new ChangeSetTool(new ChangeSet($entityManager, $sets, $check), $clock);
        $this->deleteSession = new DeleteSessionTool(new DeleteSession($entityManager, $sessions));
        $this->removeSet = new RemoveSetTool(new RemoveSet($entityManager, $sets), $clock);
        $this->addSet = new AddSetTool(new AddSet($entityManager, $sessions, $clock, $check), $clock);
        $this->updateSession = new UpdateSessionTool(new UpdateSession($entityManager, $sessions, $check, $clock), $check, $clock);
    }
}
