<?php

declare(strict_types=1);

namespace App\Session\Action;

use App\Session\SessionTransition;
use App\Session\WorkoutSession;
use App\Session\WorkoutSessionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Clock\ClockInterface;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Component\Workflow\WorkflowInterface;

final readonly class ResumeSession
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private WorkoutSessionRepository $sessions,
        private ClockInterface $clock,
        #[Target('session')]
        private WorkflowInterface $workflow,
    ) {
    }

    public function __invoke(): WorkoutSession
    {
        $session = $this->sessions->getOpen();
        if ($this->workflow->can($session, SessionTransition::Resume->value)) {
            $this->workflow->apply($session, SessionTransition::Resume->value, ['at' => $this->clock->now()]);
        }

        $this->entityManager->flush();

        return $session;
    }
}
