<?php

declare(strict_types=1);

namespace App\Tests\Exercise\Mcp;

use App\Exercise\Enum\BodyPart;
use App\Exercise\Enum\Difficulty;
use App\Exercise\Enum\ExerciseCategory;
use App\Exercise\Enum\ForceType;
use App\Exercise\Enum\Mechanic;
use App\Exercise\Exercise;
use App\Exercise\Mcp\ExerciseSummary;
use App\Exercise\Mcp\SearchExercisesTool;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class SearchExercisesToolTest extends KernelTestCase
{
    private SearchExercisesTool $search;

    protected function setUp(): void
    {
        self::bootKernel();

        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        self::assertInstanceOf(EntityManagerInterface::class, $entityManager);

        $metadata = $entityManager->getMetadataFactory()->getAllMetadata();
        $schema = new SchemaTool($entityManager);
        $schema->dropSchema($metadata);
        $schema->createSchema($metadata);

        $entityManager->persist($this->exercise('bench-press', 'Barbell Bench Press', BodyPart::Chest));
        $entityManager->persist($this->exercise('push-up', 'Push Up', BodyPart::Chest));
        $entityManager->persist($this->exercise('squat', 'Back Squat', BodyPart::UpperLegs));
        $entityManager->flush();

        $search = self::getContainer()->get(SearchExercisesTool::class);
        self::assertInstanceOf(SearchExercisesTool::class, $search);
        $this->search = $search;
    }

    public function testItAnswersWithSummariesAndTheNumberOfMatches(): void
    {
        $answer = ($this->search)(bodyPart: BodyPart::Chest);

        self::assertSame(2, $answer['matching']);
        self::assertSame(['bench-press', 'push-up'], array_map(static fn (ExerciseSummary $summary): string => $summary->id, $answer['items']));
    }

    public function testItCountsEveryMatchEvenWhenThePageIsShorter(): void
    {
        $answer = ($this->search)(bodyPart: BodyPart::Chest, limit: 1);

        self::assertSame(2, $answer['matching']);
        self::assertCount(1, $answer['items']);
    }

    private function exercise(string $id, string $name, BodyPart $bodyPart): Exercise
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
            primaryMuscles: [],
            secondaryMuscles: [],
            instructions: [],
            tags: [],
            goals: [],
            images: [],
            bodyweight: false,
            unilateral: false,
            met: 5.0,
        );
    }
}
