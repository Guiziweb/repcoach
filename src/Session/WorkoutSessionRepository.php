<?php

declare(strict_types=1);

namespace App\Session;

use App\Shared\NotFound;
use App\Shared\Reason;
use App\Shared\Refused;
use App\Workout\Workout;
use DateTimeImmutable;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<WorkoutSession>
 */
final class WorkoutSessionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, WorkoutSession::class);
    }

    public function get(int $id): WorkoutSession
    {
        return $this->find($id) ?? throw new NotFound(\sprintf('No session with id %d.', $id), Reason::SessionNotFound);
    }

    public function getOpen(): WorkoutSession
    {
        return $this->open() ?? throw new Refused('No session is open.', Reason::NoOpenSession);
    }

    public function open(): ?WorkoutSession
    {
        $session = $this->createQueryBuilder('s')
            ->andWhere('s.status IN (:open)')
            ->setParameter('open', SessionStatus::openCases())
            ->orderBy('s.startedAt', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        return $session instanceof WorkoutSession ? $session : null;
    }

    /**
     * @return list<SessionTally>
     */
    public function closed(ClosedStatus $status, ?string $workout, ?DateTimeImmutable $from, ?DateTimeImmutable $until, int $limit): array
    {
        /** @var list<array{0: WorkoutSession, sets: numeric-string|int, volume: numeric-string|float|int, reps: numeric-string|int, timedSeconds: numeric-string|int}> $rows */
        $rows = $this->filtered($status, $workout, $from, $until)
            ->select('s', 'COUNT(p.id) AS sets', 'COALESCE(SUM(COALESCE(p.reps, 0) * COALESCE(p.weight, 0)), 0) AS volume', 'COALESCE(SUM(p.reps), 0) AS reps', 'COALESCE(SUM(p.seconds), 0) AS timedSeconds')
            ->leftJoin('s.sets', 'p')
            ->groupBy('s.id')
            ->orderBy('s.startedAt', 'DESC')
            ->addOrderBy('s.id', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        return array_map(static fn (array $row): SessionTally => new SessionTally($row[0], (int) $row['sets'], (float) $row['volume'], (int) $row['reps'], (int) $row['timedSeconds']), $rows);
    }

    public function countClosed(ClosedStatus $status, ?string $workout, ?DateTimeImmutable $from, ?DateTimeImmutable $until): int
    {
        return (int) $this->filtered($status, $workout, $from, $until)
            ->select('COUNT(s.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    private function filtered(ClosedStatus $status, ?string $workout, ?DateTimeImmutable $from, ?DateTimeImmutable $until): QueryBuilder
    {
        $builder = $this->createQueryBuilder('s')
            ->andWhere('s.status = :status')
            ->setParameter('status', $status->status());

        if (null !== $workout && '' !== trim($workout)) {
            $builder->andWhere('s.workoutName IN (:workouts)')->setParameter('workouts', $this->namesMatching($workout) ?: ['']);
        }

        if (null !== $from) {
            $builder->andWhere('s.startedAt >= :from')->setParameter('from', $from);
        }

        if (null !== $until) {
            $builder->andWhere('s.startedAt < :until')->setParameter('until', $until);
        }

        return $builder;
    }

    /**
     * @return list<string>
     */
    private function namesMatching(string $workout): array
    {
        $wanted = mb_strtolower(trim($workout));
        /** @var list<string> $names */
        $names = $this->createQueryBuilder('s')->select('DISTINCT s.workoutName')->getQuery()->getSingleColumnResult();

        return array_values(array_filter($names, static fn (string $name): bool => mb_strtolower($name) === $wanted));
    }

    /**
     * @return list<WorkoutSession>
     */
    public function of(Workout $workout): array
    {
        /** @var list<WorkoutSession> $sessions */
        $sessions = $this->createQueryBuilder('s')
            ->andWhere('s.workout = :workout')
            ->setParameter('workout', $workout)
            ->getQuery()
            ->getResult();

        return $sessions;
    }
}
