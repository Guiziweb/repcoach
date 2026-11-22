<?php

declare(strict_types=1);

namespace App\Tests\Web\Session\Coach;

use App\Session\PerformedSet;
use App\Session\SessionTally;
use App\Session\WorkoutSession;
use App\Tests\ExerciseFixture;
use App\Web\Session\Coach\LiveSessionDescription;
use App\Web\Session\Coach\SessionDescription;
use App\Web\Session\Coach\SessionsDescription;
use App\Web\Session\View\Duration;
use App\Web\Session\View\LiveSessionView;
use App\Web\Session\View\SessionRow;
use App\Web\Session\View\SessionView;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;

final class DescriptionsTest extends TestCase
{
    public function testTheLiveSessionSaysWhatIsDoneAndWhatIsLeft(): void
    {
        $session = self::session();
        ExerciseFixture::set($session, self::at('10:05'), reps: 8, weight: 60.0);

        $description = LiveSessionDescription::of(LiveSessionView::of($session, new Duration(600)));

        self::assertStringContainsString('workout "Pecs", running, started 10:00, 10 minutes so far', $description);
        self::assertStringContainsString('Line 1, Barbell Bench Press, planned 4 sets of 8 reps at 60 kg', $description);
        self::assertStringContainsString('8 reps at 60 kg, 3 left', $description);
    }

    public function testTheLiveSessionMentionsTheSetsOutsideThePlan(): void
    {
        $session = self::session();
        self::saved(new PerformedSet($session, ExerciseFixture::exercise('plank', 'Plank', bodyweight: true), null, self::at('10:10'), null, 60, null), 41);

        self::assertStringContainsString('Off plan, Plank: set id 41: 60 seconds', LiveSessionDescription::of(LiveSessionView::of($session, new Duration(600))));
    }

    public function testWithoutAnOpenSessionTheScreenInvitesToStartOne(): void
    {
        self::assertStringContainsString('No session is open', LiveSessionDescription::of(null));
    }

    public function testAFinishedSessionListsEverySetTheScreenShows(): void
    {
        $session = self::session();
        self::saved(ExerciseFixture::set($session, self::at('10:05'), reps: 8, weight: 60.0), 41);
        self::saved(new PerformedSet($session, ExerciseFixture::exercise('plank', 'Plank', bodyweight: true), null, self::at('10:10'), null, 60, null), 42);

        $description = SessionDescription::of(SessionView::of($session, new Duration(1_800), null, null, 1));

        self::assertStringContainsString('lasted 30 minutes', $description);
        self::assertStringContainsString('1. Barbell Bench Press: set id 41: 8 reps at 60 kg', $description);
        self::assertStringContainsString('2. Plank: set id 42: 60 seconds', $description);
    }

    public function testAMissingSessionIsSaidToBeGone(): void
    {
        self::assertSame('A session that no longer exists.', SessionDescription::of(null));
    }

    public function testTheListGivesOneLinePerSession(): void
    {
        $row = new SessionRow(new SessionTally(self::session(), 3, 1_440.0, 24, 0), new Duration(2_700));

        $description = SessionsDescription::of([$row], 2, 3);

        self::assertStringContainsString('page 2 of 3', $description);
        self::assertStringContainsString('Pecs on Sun 22 Nov 2026 10:00, 45 minutes, 3 sets, 1440 kg', $description);
    }

    public function testAnEmptyListSaysSo(): void
    {
        self::assertSame('The list of finished sessions, empty.', SessionsDescription::of([], 1, 1));
    }

    private static function saved(PerformedSet $set, int $id): void
    {
        new ReflectionProperty(PerformedSet::class, 'id')->setValue($set, $id);
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
