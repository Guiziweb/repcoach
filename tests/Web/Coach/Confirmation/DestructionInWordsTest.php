<?php

declare(strict_types=1);

namespace App\Tests\Web\Coach\Confirmation;

use App\Session\PerformedSet;
use App\Session\WorkoutSession;
use App\Tests\ExerciseFixture;
use App\Tests\ResetsTheDatabase;
use App\Web\Coach\Confirmation\DestructionInWords;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\AI\Platform\Result\ToolCall;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class DestructionInWordsTest extends KernelTestCase
{
    use ResetsTheDatabase;

    private DestructionInWords $words;

    private WorkoutSession $session;

    private PerformedSet $set;

    protected function setUp(): void
    {
        self::bootKernel();
        $entityManager = $this->emptyDatabase();

        $exercise = ExerciseFixture::exercise();
        $workout = ExerciseFixture::workout('Pecs', $exercise);
        $this->session = new WorkoutSession(new DateTimeImmutable('2026-11-22 10:00'), $workout);
        $this->set = ExerciseFixture::set($this->session, new DateTimeImmutable('2026-11-22 10:05'), reps: 8, weight: 60.0);

        $entityManager->persist($exercise);
        $entityManager->persist($workout);
        $entityManager->persist($this->session);
        $entityManager->flush();

        $words = self::getContainer()->get(DestructionInWords::class);
        self::assertInstanceOf(DestructionInWords::class, $words);
        $this->words = $words;
    }

    public function testItNamesTheWorkoutToDelete(): void
    {
        self::assertSame('delete the workout “Pecs”', $this->words->of(self::call('delete_workout', ['workout' => 'pecs'])));
    }

    public function testItNamesTheExerciseToRemove(): void
    {
        self::assertSame('remove Barbell Bench Press from “Pecs”', $this->words->of(self::call('remove_exercise', ['workout' => 'Pecs', 'position' => 1])));
    }

    public function testItDatesTheSessionToDelete(): void
    {
        self::assertSame('delete the Pecs session of November 22, 2026', $this->words->of(self::call('delete_session', ['sessionId' => $this->session->getId()])));
    }

    public function testItDescribesTheSetToDelete(): void
    {
        self::assertSame('delete the 8 × 60 kg set of Barbell Bench Press', $this->words->of(self::call('remove_set', ['setId' => $this->set->getId()])));
    }

    public function testItNamesTheSessionToAbort(): void
    {
        self::assertSame('abort the running Pecs session', $this->words->of(self::call('abort_session', [])));
    }

    /**
     * @param array<string, mixed> $arguments
     */
    #[DataProvider('unknownTargets')]
    public function testItFallsBackOnTheToolWhenTheTargetIsUnknown(string $tool, array $arguments, string $words): void
    {
        self::assertSame($words, $this->words->of(self::call($tool, $arguments)));
    }

    /**
     * @return iterable<string, array{string, array<string, mixed>, string}>
     */
    public static function unknownTargets(): iterable
    {
        yield 'missing workout' => ['delete_workout', ['workout' => 'Legs'], 'delete workout'];
        yield 'missing line' => ['remove_exercise', ['workout' => 'Pecs', 'position' => 9], 'remove exercise'];
        yield 'missing session' => ['delete_session', ['sessionId' => 999], 'delete session'];
        yield 'missing argument' => ['remove_set', [], 'remove set'];
    }

    /**
     * @param array<string, mixed> $arguments
     */
    private static function call(string $tool, array $arguments): ToolCall
    {
        return new ToolCall('toolu_1', 'repcoach_'.$tool, $arguments);
    }
}
