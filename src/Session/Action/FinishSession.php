<?php

declare(strict_types=1);

namespace App\Session\Action;

use App\Session\SessionTransition;
use App\Session\WorkoutSession;
use App\Session\WorkoutSessionRepository;
use App\Shared\Check;
use App\Shared\Minutes;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Clock\ClockInterface;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Component\Workflow\WorkflowInterface;

final readonly class FinishSession
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private WorkoutSessionRepository $sessions,
        private ClockInterface $clock,
        #[Target('session')]
        private WorkflowInterface $workflow,
        private Check $check,
    ) {
    }

    public function __invoke(?int $durationMinutes): WorkoutSession
    {
        ($this->check)($durationMinutes, new Minutes());

        $session = $this->sessions->getOpen();
        $this->workflow->apply($session, SessionTransition::Finish->value, ['at' => $this->clock->now(), 'lasted' => $durationMinutes]);
        $this->entityManager->flush();

        return $session;
    }
}
