<?php

declare(strict_types=1);

namespace App\Web\Coach\Display;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\AI\Agent\Toolbox\Event\ToolCallArgumentsResolved;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

#[AsEventListener]
final readonly class DisplayToolsReadTheDatabaseAfresh
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function __invoke(ToolCallArgumentsResolved $event): void
    {
        $this->entityManager->clear();
    }
}
