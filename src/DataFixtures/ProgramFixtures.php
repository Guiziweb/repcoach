<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Exercise\Exercise;
use App\Workout\Workout;
use App\Workout\WorkoutExercise;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use LogicException;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final class ProgramFixtures extends Fixture
{
    /**
     * @param array<string, list<array{exercise: string, sets: int, reps?: int, seconds?: int, weight?: int|float}>> $programs
     */
    public function __construct(
        #[Autowire('%app.programs%')]
        private readonly array $programs,
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        foreach ($this->programs as $name => $lines) {
            $workout = new Workout($name);

            foreach ($lines as $line) {
                new WorkoutExercise(
                    $workout,
                    $this->exercise($manager, $line['exercise']),
                    $workout->nextPosition(),
                    $line['sets'],
                    $line['reps'] ?? null,
                    $line['seconds'] ?? null,
                    $line['weight'] ?? null,
                );
            }

            $manager->persist($workout);
        }

        $manager->flush();
    }

    private function exercise(ObjectManager $manager, string $id): Exercise
    {
        $exercise = $manager->find(Exercise::class, $id);

        if (!$exercise instanceof Exercise) {
            throw new LogicException(\sprintf('Exercise "%s" is not in the catalogue. Run app:import-exercises first.', $id));
        }

        return $exercise;
    }
}
