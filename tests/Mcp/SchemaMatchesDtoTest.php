<?php

declare(strict_types=1);

namespace App\Tests\Mcp;

use App\Exercise\Mcp\ExerciseSummary;
use App\Session\Mcp\PerformedSetView;
use App\Session\Mcp\SessionOverview;
use App\Session\Mcp\SessionPlanView;
use App\Session\Mcp\SessionSummary;
use App\Workout\Mcp\PlannedExerciseView;
use App\Workout\Mcp\WorkoutSummary;
use Mcp\Capability\Discovery\DocBlockParser;
use Mcp\Capability\Discovery\SchemaGenerator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

final class SchemaMatchesDtoTest extends TestCase
{
    /**
     * @param class-string         $dto
     * @param array<string, mixed> $schema
     */
    #[DataProvider('summaries')]
    public function testTheSchemaStillDescribesTheDto(string $dto, array $schema): void
    {
        $derived = (new SchemaGenerator(new DocBlockParser()))
            ->generate(new ReflectionMethod($dto, '__construct'));

        self::assertIsArray($derived['properties']);
        self::assertIsArray($schema['properties']);
        self::assertSame(array_keys($derived['properties']), array_keys($schema['properties']));
        self::assertSame($derived['required'], $schema['required']);
    }

    /**
     * @return iterable<string, array{class-string, array<string, mixed>}>
     */
    public static function summaries(): iterable
    {
        yield 'exercise' => [ExerciseSummary::class, ExerciseSummary::SCHEMA];
        yield 'workout' => [WorkoutSummary::class, WorkoutSummary::SCHEMA];
        yield 'session' => [SessionSummary::class, SessionSummary::SCHEMA];
        yield 'session overview' => [SessionOverview::class, SessionOverview::SCHEMA];
        yield 'performed set' => [PerformedSetView::class, PerformedSetView::SCHEMA];
        yield 'planned exercise' => [PlannedExerciseView::class, PlannedExerciseView::SCHEMA];
        yield 'session plan' => [SessionPlanView::class, SessionPlanView::SCHEMA];
    }
}
