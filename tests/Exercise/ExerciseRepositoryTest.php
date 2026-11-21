<?php

declare(strict_types=1);

namespace App\Tests\Exercise;

use App\Exercise\Enum\BodyPart;
use App\Exercise\Enum\Difficulty;
use App\Exercise\Enum\Equipment;
use App\Exercise\Enum\ExerciseCategory;
use App\Exercise\Enum\ForceType;
use App\Exercise\Enum\Mechanic;
use App\Exercise\Enum\Muscle;
use App\Exercise\Exercise;
use App\Exercise\ExerciseRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

#[CoversClass(ExerciseRepository::class)]
final class ExerciseRepositoryTest extends KernelTestCase
{
    private ExerciseRepository $exercises;

    protected function setUp(): void
    {
        self::bootKernel();

        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        self::assertInstanceOf(EntityManagerInterface::class, $entityManager);

        $schema = new SchemaTool($entityManager);
        $schema->dropSchema($entityManager->getMetadataFactory()->getAllMetadata());
        $schema->createSchema($entityManager->getMetadataFactory()->getAllMetadata());

        foreach ($this->catalogue() as $exercise) {
            $entityManager->persist($exercise);
        }

        $entityManager->flush();

        $repository = self::getContainer()->get(ExerciseRepository::class);
        self::assertInstanceOf(ExerciseRepository::class, $repository);
        $this->exercises = $repository;
    }

    public function testItReturnsEverythingWithoutFilters(): void
    {
        self::assertCount(4, $this->exercises->search());
    }

    public function testItFiltersByBodyPart(): void
    {
        $found = $this->exercises->search(bodyPart: BodyPart::Chest);

        self::assertSame(['bench-press', 'push-up'], array_map(static fn (Exercise $e): string => $e->id, $found));
    }

    public function testItFiltersByMuscleInsideTheJsonColumn(): void
    {
        $found = $this->exercises->search(muscle: Muscle::Quadriceps);

        self::assertCount(1, $found);
        self::assertSame('squat', $found[0]->id);
    }

    public function testItDoesNotMatchALongerMuscleName(): void
    {
        self::assertCount(0, $this->exercises->search(muscle: Muscle::Forearms));
    }

    public function testItAlsoMatchesASecondaryMuscle(): void
    {
        $found = $this->exercises->search(muscle: Muscle::TricepsBrachii);

        self::assertSame(['bench-press'], array_map(static fn (Exercise $e): string => $e->id, $found));
    }

    public function testAQueryWithoutWordsFiltersNothing(): void
    {
        self::assertCount(4, $this->exercises->search(query: '%_"['));
    }

    public function testAnAccentedLetterStaysPartOfItsWord(): void
    {
        self::assertCount(0, $this->exercises->search(query: 'pressé'));
    }

    public function testEveryWordMustMatchSomewhereInAnyOrder(): void
    {
        $found = $this->exercises->search(query: 'chest barbell');

        self::assertSame(['bench-press'], array_map(static fn (Exercise $exercise): string => $exercise->id, $found));
        self::assertCount(1, $this->exercises->search(query: 'press bench'));
        self::assertCount(0, $this->exercises->search(query: 'chest squat'));
    }

    public function testAnUnderscoreStillMatchesTheSnakeCaseMuscleNames(): void
    {
        self::assertCount(1, $this->exercises->search(query: 'forearm_extensors'));
    }

    public function testMusclesAndBodyPartsCanBeWrittenInPlainWords(): void
    {
        self::assertCount(1, $this->exercises->search(query: 'forearm extensors'));
        self::assertCount(1, $this->exercises->search(query: 'Lower Arms'));
        self::assertCount(1, $this->exercises->search(query: 'upper-legs'));
    }

    public function testItFindsAnExerciseByTheMuscleItWorks(): void
    {
        $found = $this->exercises->search(query: 'pectoralis');

        self::assertSame(['bench-press', 'push-up'], array_map(static fn (Exercise $e): string => $e->id, $found));
    }

    public function testItFindsAnExerciseBySecondaryMuscleToo(): void
    {
        self::assertCount(1, $this->exercises->search(query: 'triceps'));
    }

    public function testItFindsAnExerciseByBodyPart(): void
    {
        self::assertCount(2, $this->exercises->search(query: 'chest'));
    }

    public function testItFiltersByEquipmentFreeExercises(): void
    {
        $found = $this->exercises->search(bodyweight: true);

        self::assertSame(['push-up'], array_map(static fn (Exercise $e): string => $e->id, $found));
    }

    public function testItSearchesCaseInsensitively(): void
    {
        self::assertCount(1, $this->exercises->search(query: 'BENCH'));
    }

    public function testItFiltersByEquipment(): void
    {
        $found = $this->exercises->search(equipment: Equipment::Dumbbell);

        self::assertSame(['curl'], array_map(static fn (Exercise $exercise): string => $exercise->id, $found));
        self::assertSame(1, $this->exercises->countMatching(equipment: Equipment::Dumbbell));
    }

    public function testItCombinesFilters(): void
    {
        self::assertCount(0, $this->exercises->search(bodyPart: BodyPart::Chest, difficulty: Difficulty::Advanced));
    }

    public function testItNeverReturnsMoreThanFifty(): void
    {
        self::assertCount(4, $this->exercises->search(limit: 5000));
    }

    public function testItAlwaysReturnsAtLeastOne(): void
    {
        self::assertCount(1, $this->exercises->search(limit: 0));
    }

    /**
     * @return list<Exercise>
     */
    private function catalogue(): array
    {
        return [
            $this->exercise('bench-press', 'Barbell Bench Press', BodyPart::Chest, ['pectoralis_major'], false, ['triceps_brachii']),
            $this->exercise('push-up', 'Push Up', BodyPart::Chest, ['pectoralis_major'], true),
            $this->exercise('squat', 'Back Squat', BodyPart::UpperLegs, ['quadriceps'], false, equipment: Equipment::Barbell),
            $this->exercise('curl', 'Wrist Curl', BodyPart::LowerArms, ['forearm_extensors'], false, equipment: Equipment::Dumbbell),
        ];
    }

    public function testItCountsEveryMatchNotJustThePage(): void
    {
        self::assertSame(4, $this->exercises->countMatching());
        self::assertCount(1, $this->exercises->search(limit: 1));
        self::assertSame(2, $this->exercises->countMatching(bodyPart: BodyPart::Chest));
    }

    /**
     * @param list<string> $muscles
     * @param list<string> $secondary
     */
    private function exercise(string $id, string $name, BodyPart $bodyPart, array $muscles, bool $bodyweight, array $secondary = [], ?Equipment $equipment = null): Exercise
    {
        return new Exercise(
            id: $id,
            name: $name,
            description: 'Description.',
            category: ExerciseCategory::Strength,
            bodyPart: $bodyPart,
            forceType: ForceType::Push,
            mechanic: Mechanic::Compound,
            difficulty: Difficulty::Intermediate,
            primaryMuscles: $muscles,
            secondaryMuscles: $secondary,
            instructions: ['Do it.'],
            tags: [],
            goals: [],
            bodyweight: $bodyweight,
            unilateral: false,
            met: 5.0,
            equipment: $equipment,
        );
    }
}
