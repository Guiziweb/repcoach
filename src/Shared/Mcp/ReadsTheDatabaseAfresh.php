<?php

declare(strict_types=1);

namespace App\Shared\Mcp;

use Doctrine\ORM\EntityManagerInterface;
use Mcp\Event\RequestEvent;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

#[AsEventListener]
final readonly class ReadsTheDatabaseAfresh
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function __invoke(RequestEvent $event): void
    {
        $this->entityManager->clear();
    }
}
