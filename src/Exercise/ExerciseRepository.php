<?php

declare(strict_types=1);

namespace App\Exercise;

use App\Exercise\Enum\BodyPart;
use App\Exercise\Enum\Difficulty;
use App\Exercise\Enum\ExerciseCategory;
use App\Exercise\Enum\Muscle;
use App\Shared\NotFound;
use App\Shared\Reason;
use BackedEnum;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Exercise>
 */
final class ExerciseRepository extends ServiceEntityRepository
{
    public const int MAX_RESULTS = 50;

    private const string LIKE_ESCAPE = '!';

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Exercise::class);
    }

    public function get(string $id): Exercise
    {
        return $this->find($id) ?? throw new NotFound(\sprintf('Unknown exercise "%s".', $id), Reason::ExerciseNotFound);
    }

    /**
     * @return list<Exercise>
     */
    public function search(
        ?BodyPart $bodyPart = null,
        ?ExerciseCategory $category = null,
        ?Difficulty $difficulty = null,
        ?Muscle $muscle = null,
        ?string $query = null,
        ?bool $bodyweight = null,
        int $limit = 20,
    ): array {
        /** @var list<Exercise> $found */
        $found = $this->filtered($bodyPart, $category, $difficulty, $muscle, $query, $bodyweight)
            ->setMaxResults(max(1, min($limit, self::MAX_RESULTS)))
            ->orderBy('e.name', 'ASC')
            ->getQuery()
            ->getResult();

        return $found;
    }

    public function countMatching(
        ?BodyPart $bodyPart = null,
        ?ExerciseCategory $category = null,
        ?Difficulty $difficulty = null,
        ?Muscle $muscle = null,
        ?string $query = null,
        ?bool $bodyweight = null,
    ): int {
        return (int) $this->filtered($bodyPart, $category, $difficulty, $muscle, $query, $bodyweight)
            ->select('COUNT(e.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    private function filtered(
        ?BodyPart $bodyPart,
        ?ExerciseCategory $category,
        ?Difficulty $difficulty,
        ?Muscle $muscle,
        ?string $query,
        ?bool $bodyweight,
    ): QueryBuilder {
        $builder = $this->createQueryBuilder('e');

        $this->whereEquals($builder, 'bodyPart', $bodyPart);
        $this->whereEquals($builder, 'category', $category);
        $this->whereEquals($builder, 'difficulty', $difficulty);
        $this->whereEquals($builder, 'bodyweight', $bodyweight);

        if (null !== $muscle) {
            $builder
                ->andWhere(\sprintf('e.primaryMuscles LIKE :muscle ESCAPE \'%1$s\' OR e.secondaryMuscles LIKE :muscle ESCAPE \'%1$s\'', self::LIKE_ESCAPE))
                ->setParameter('muscle', '%"'.$this->escapeForLike($muscle->value).'"%');
        }

        if (null !== $query && '' !== $query) {
            $builder
                ->andWhere(\sprintf(
                    'LOWER(e.name) LIKE :query ESCAPE \'%1$s\' OR LOWER(e.primaryMuscles) LIKE :query ESCAPE \'%1$s\' OR LOWER(e.secondaryMuscles) LIKE :query ESCAPE \'%1$s\' OR LOWER(e.bodyPart) LIKE :query ESCAPE \'%1$s\'',
                    self::LIKE_ESCAPE,
                ))
                ->setParameter('query', '%'.$this->escapeForLike(mb_strtolower($query)).'%');
        }

        return $builder;
    }

    private function whereEquals(QueryBuilder $query, string $field, BackedEnum|bool|null $value): void
    {
        if (null !== $value) {
            $query->andWhere(\sprintf('e.%s = :%s', $field, $field))->setParameter($field, $value);
        }
    }

    private function escapeForLike(string $term): string
    {
        return $this->getEntityManager()
            ->getConnection()
            ->getDatabasePlatform()
            ->escapeStringForLike($term, self::LIKE_ESCAPE);
    }
}
