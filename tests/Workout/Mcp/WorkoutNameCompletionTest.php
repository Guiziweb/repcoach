<?php

declare(strict_types=1);

namespace App\Tests\Workout\Mcp;

use App\Workout\Mcp\WorkoutNameCompletion;
use App\Workout\Workout;
use App\Workout\WorkoutRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class WorkoutNameCompletionTest extends KernelTestCase
{
    private WorkoutNameCompletion $completion;

    protected function setUp(): void
    {
        self::bootKernel();

        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        self::assertInstanceOf(EntityManagerInterface::class, $entityManager);

        $metadata = $entityManager->getMetadataFactory()->getAllMetadata();
        $schema = new SchemaTool($entityManager);
        $schema->dropSchema($metadata);
        $schema->createSchema($metadata);

        $entityManager->persist(new Workout('Pecs'));
        $entityManager->persist(new Workout('Pecs lourd'));
        $entityManager->persist(new Workout('Dos'));
        $entityManager->flush();

        $workouts = self::getContainer()->get(WorkoutRepository::class);
        self::assertInstanceOf(WorkoutRepository::class, $workouts);
        $this->completion = new WorkoutNameCompletion($workouts);
    }

    public function testItOffersEveryWorkoutBeforeAnythingIsTyped(): void
    {
        self::assertSame(['Dos', 'Pecs', 'Pecs lourd'], $this->completion->getCompletions(''));
    }

    public function testItOffersTheNamesThatStartWithWhatIsTypedWhateverTheCase(): void
    {
        self::assertSame(['Pecs', 'Pecs lourd'], $this->completion->getCompletions(' pEC '));
    }

    public function testItOffersNothingWhenNoNameMatches(): void
    {
        self::assertSame([], $this->completion->getCompletions('jambes'));
    }
}
