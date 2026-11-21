<?php

declare(strict_types=1);

namespace App\Session\Action;

use App\Session\PerformedSetRepository;
use App\Session\WorkoutSession;
use Doctrine\ORM\EntityManagerInterface;

final readonly class RemoveSet
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private PerformedSetRepository $sets,
    ) {
    }

    public function __invoke(int $set): WorkoutSession
    {
        $found = $this->sets->get($set);
        $session = $found->session;

        $session->removeSet($found);
        $this->entityManager->flush();

        return $session;
    }
}
