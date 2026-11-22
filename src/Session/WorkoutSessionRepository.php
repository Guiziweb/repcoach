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
    public function closed(ClosedStatus $status, ?string $workout, ?DateTimeImmutable $from, ?DateTimeImmutable $until, int $limit, int $offset = 0): array
    {
        /** @var list<array{0: WorkoutSession, sets: numeric-string|int, volume: numeric-string|float|int, reps: numeric-string|int, timedSeconds: numeric-string|int}> $rows */
        $rows = $this->filtered($status, $workout, $from, $until)
            ->select('s', 'COUNT(p.id) AS sets', 'COALESCE(SUM(COALESCE(p.reps, 0) * COALESCE(p.weight, 0)), 0) AS volume', 'COALESCE(SUM(p.reps), 0) AS reps', 'COALESCE(SUM(p.seconds), 0) AS timedSeconds')
            ->leftJoin('s.sets', 'p')
            ->groupBy('s.id')
            ->orderBy('s.startedAt', 'DESC')
            ->addOrderBy('s.id', 'DESC')
            ->setMaxResults($limit)
            ->setFirstResult($offset)
            ->getQuery()
            ->getResult();

        return array_map(static fn (array $row): SessionTally => new SessionTally($row[0], (int) $row['sets'], (float) $row['volume'], (int) $row['reps'], (int) $row['timedSeconds']), $rows);
    }

    /**
     * @return int<0, max>
     */
    public function countClosed(ClosedStatus $status, ?string $workout, ?DateTimeImmutable $from, ?DateTimeImmutable $until): int
    {
        return max(0, (int) $this->filtered($status, $workout, $from, $until)
            ->select('COUNT(s.id)')
            ->getQuery()
            ->getSingleScalarResult());
    }

    public function closedBefore(WorkoutSession $session, ClosedStatus $status): ?WorkoutSession
    {
        $found = $this->beside($session, $status, '<', 'DESC')->setMaxResults(1)->getQuery()->getOneOrNullResult();

        return $found instanceof WorkoutSession ? $found : null;
    }

    public function closedAfter(WorkoutSession $session, ClosedStatus $status): ?WorkoutSession
    {
        $found = $this->beside($session, $status, '>', 'ASC')->setMaxResults(1)->getQuery()->getOneOrNullResult();

        return $found instanceof WorkoutSession ? $found : null;
    }

    public function countClosedAfter(WorkoutSession $session, ClosedStatus $status): int
    {
        return (int) $this->beside($session, $status, '>', 'ASC')->select('COUNT(s.id)')->resetDQLPart('orderBy')->getQuery()->getSingleScalarResult();
    }

    private function beside(WorkoutSession $session, ClosedStatus $status, string $direction, string $order): QueryBuilder
    {
        return $this->filtered($status, null, null, null)
            ->andWhere(\sprintf('s.startedAt %1$s :at OR (s.startedAt = :at AND s.id %1$s :id)', $direction))
            ->setParameter('at', $session->startedAt)
            ->setParameter('id', $session->getId())
            ->orderBy('s.startedAt', $order)
            ->addOrderBy('s.id', $order);
    }

    private function filtered(ClosedStatus $status, ?string $workout, ?DateTimeImmutable $from, ?DateTimeImmutable $until): QueryBuilder
    {
        $builder = $this->createQueryBuilder('s')
            ->andWhere('s.status = :status')
            ->setParameter('status', $status->status());

        if (null !== $workout && '' !== trim($workout)) {
            $builder
                ->andWhere('(s.workout IN (:workouts) OR s.workoutName IN (:names))')
                ->setParameter('workouts', $this->workoutsNamed($workout) ?: [0])
                ->setParameter('names', $this->namesMatching($workout) ?: ['']);
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
     * @return list<int>
     */
    private function workoutsNamed(string $workout): array
    {
        $wanted = mb_strtolower(trim($workout));
        /** @var list<array{id: int, name: string}> $workouts */
        $workouts = $this->getEntityManager()->createQueryBuilder()
            ->select('w.id', 'w.name')
            ->from(Workout::class, 'w')
            ->getQuery()
            ->getArrayResult();

        return array_values(array_map(
            static fn (array $workout): int => $workout['id'],
            array_filter($workouts, static fn (array $workout): bool => mb_strtolower($workout['name']) === $wanted),
        ));
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
