<?php

declare(strict_types=1);

namespace App\Session\Action;

use App\Session\WorkoutSessionRepository;
use App\Shared\Reason;
use App\Shared\Refused;
use Doctrine\ORM\EntityManagerInterface;

final readonly class DeleteSession
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private WorkoutSessionRepository $sessions,
    ) {
    }

    public function __invoke(int $session): void
    {
        $found = $this->sessions->get($session);

        if ($found->getStatus()->isOpen()) {
            throw new Refused('That session is still open.', Reason::SessionStillOpenForDeletion);
        }

        $this->entityManager->remove($found);
        $this->entityManager->flush();
    }
}
