<?php

declare(strict_types=1);

namespace App\Workout;

use App\Shared\NotFound;
use App\Shared\Reason;
use App\Shared\Refused;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Workout>
 */
final class WorkoutRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Workout::class);
    }

    /**
     * @return list<Workout>
     */
    public function all(): array
    {
        /** @var list<Workout> $workouts */
        $workouts = $this->createQueryBuilder('w')
            ->leftJoin('w.exercises', 'e')
            ->addSelect('e')
            ->orderBy('w.name', 'ASC')
            ->getQuery()
            ->getResult();

        return $workouts;
    }

    public function named(string $name): ?Workout
    {
        $wanted = mb_strtolower(trim($name));
        $workout = null;

        foreach ($this->findAll() as $candidate) {
            if (mb_strtolower($candidate->name) === $wanted) {
                $workout = $candidate;

                break;
            }
        }

        return $workout;
    }

    public function ensureFree(string $name, ?Workout $besides = null): string
    {
        $taken = $this->named($name);

        if ($taken instanceof Workout && $taken !== $besides) {
            throw new Refused(\sprintf('A workout named "%s" already exists.', $name));
        }

        return $name;
    }

    public function get(string $name): Workout
    {
        return $this->named($name) ?? throw new NotFound(\sprintf('No workout named "%s".', $name), Reason::WorkoutNotFound);
    }
}
