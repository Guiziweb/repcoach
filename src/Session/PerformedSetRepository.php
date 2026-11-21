<?php

declare(strict_types=1);

namespace App\Session;

use App\Shared\NotFound;
use App\Shared\Reason;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PerformedSet>
 */
final class PerformedSetRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PerformedSet::class);
    }

    public function get(int $id): PerformedSet
    {
        return $this->find($id) ?? throw new NotFound(\sprintf('No set with id %d.', $id), Reason::SetNotFound);
    }
}
