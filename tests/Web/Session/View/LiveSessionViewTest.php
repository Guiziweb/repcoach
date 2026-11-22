<?php

declare(strict_types=1);

namespace App\Tests\Web\Session\View;

use App\Session\PerformedSet;
use App\Session\WorkoutSession;
use App\Tests\ExerciseFixture;
use App\Web\Session\View\Duration;
use App\Web\Session\View\LiveSessionView;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class LiveSessionViewTest extends TestCase
{
    public function testEachPlannedLineKeepsItsOwnSets(): void
    {
        $session = self::session();
        ExerciseFixture::set($session, self::at('10:05'), reps: 8, weight: 60.0);

        $view = LiveSessionView::of($session, new Duration(300));

        self::assertCount(1, $view->lines);
        self::assertCount(1, $view->lines[0]->done);
        self::assertSame(3, $view->lines[0]->remaining());
    }

    public function testASetOutsideThePlanIsKeptApart(): void
    {
        $session = self::session();
        $plank = ExerciseFixture::exercise('plank', 'Plank', bodyweight: true);
        new PerformedSet($session, $plank, null, self::at('10:10'), null, 60, null);

        $view = LiveSessionView::of($session, new Duration(600));

        self::assertSame([], $view->lines[0]->done);
        self::assertCount(1, $view->extras['Plank']);
    }

    public function testALineIsNeverMoreThanDone(): void
    {
        $session = self::session();

        foreach (['10:01', '10:02', '10:03', '10:04', '10:05'] as $at) {
            ExerciseFixture::set($session, self::at($at), reps: 8, weight: 60.0);
        }

        self::assertSame(0, LiveSessionView::of($session, new Duration(300))->lines[0]->remaining());
    }

    private static function session(): WorkoutSession
    {
        return new WorkoutSession(self::at('10:00'), ExerciseFixture::workout('Pecs'));
    }

    private static function at(string $time): DateTimeImmutable
    {
        return new DateTimeImmutable("2026-11-22 {$time}:00");
    }
}
