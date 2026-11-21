<?php

declare(strict_types=1);

namespace App\Tests\Session;

use App\Session\SessionNotRunning;
use App\Session\SessionStatus;
use App\Session\SessionTransition;
use App\Session\WorkoutSession;
use App\Tests\ExerciseFixture;
use DateTimeImmutable;
use LogicException;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Workflow\WorkflowInterface;

#[CoversClass(WorkoutSession::class)]
final class WorkoutSessionTest extends KernelTestCase
{
    public function testItStartsRunning(): void
    {
        self::assertSame(SessionStatus::Running, $this->session()->getStatus());
    }

    public function testItsDurationIgnoresThePauses(): void
    {
        $session = $this->session();
        $this->apply($session, SessionTransition::Pause, '10:20');
        $this->apply($session, SessionTransition::Resume, '10:30');
        $this->apply($session, SessionTransition::Finish, '11:00');

        self::assertSame(50 * 60, $session->durationInSeconds($this->at('12:00')));
    }

    public function testItsDurationStopsGrowingWhilePaused(): void
    {
        $session = $this->session();
        $this->apply($session, SessionTransition::Pause, '10:20');

        self::assertSame(20 * 60, $session->durationInSeconds($this->at('10:45')));
        self::assertSame(20 * 60, $session->durationInSeconds($this->at('11:30')));
    }

    public function testAPausedSessionCannotBePausedAgain(): void
    {
        $session = $this->session();
        $this->apply($session, SessionTransition::Pause, '10:10');

        self::assertFalse($this->workflow()->can($session, SessionTransition::Pause->value));
    }

    public function testFinishingWhilePausedClosesThePause(): void
    {
        $session = $this->session();
        $this->apply($session, SessionTransition::Pause, '10:20');
        $this->apply($session, SessionTransition::Finish, '10:50');

        self::assertSame(SessionStatus::Finished, $session->getStatus());
        self::assertSame(20 * 60, $session->durationInSeconds($this->at('11:00')));
    }

    public function testAClosedSessionAllowsNoTransition(): void
    {
        $session = $this->session();
        $this->apply($session, SessionTransition::Finish, '10:30');

        self::assertSame([], $this->workflow()->getEnabledTransitions($session));
        self::assertSame(SessionStatus::Finished, $session->getStatus());
        self::assertEquals($this->at('10:30'), $session->getEndedAt());
    }

    public function testARunningSessionCanBePausedFinishedOrAborted(): void
    {
        $names = array_map(static fn ($transition): string => $transition->getName(), $this->workflow()->getEnabledTransitions($this->session()));
        sort($names);

        self::assertSame($this->named(SessionTransition::Abort, SessionTransition::Finish, SessionTransition::Pause), $names);
    }

    public function testAPausedSessionCanBeResumedFinishedOrAborted(): void
    {
        $session = $this->session();
        $this->apply($session, SessionTransition::Pause, '10:10');

        $names = array_map(static fn ($transition): string => $transition->getName(), $this->workflow()->getEnabledTransitions($session));
        sort($names);

        self::assertSame($this->named(SessionTransition::Abort, SessionTransition::Finish, SessionTransition::Resume), $names);
    }

    public function testAStatusChangeNeedsTheMomentItHappens(): void
    {
        $this->expectException(LogicException::class);

        $this->workflow()->apply($this->session(), SessionTransition::Pause->value);
    }

    public function testFinishingWithTheRealDurationMovesTheStart(): void
    {
        $session = $this->session();
        $this->workflow()->apply($session, SessionTransition::Finish->value, ['at' => $this->at('10:30'), 'lasted' => 45]);

        self::assertEquals($this->at('09:45'), $session->startedAt);
        self::assertSame(45 * 60, $session->durationInSeconds($this->at('12:00')));
    }

    public function testMovingTheStartMovesTheSetsWithIt(): void
    {
        $session = $this->session();
        $set = ExerciseFixture::set($session, $this->at('10:05'), reps: 8, weight: 60.0);
        $this->apply($session, SessionTransition::Finish, '10:30');

        $session->reschedule($this->at('09:00'), null);

        self::assertEquals($this->at('09:05'), $set->loggedAt);
        self::assertEquals($this->at('09:30'), $session->getEndedAt());
    }

    public function testASetNeverEndsUpAfterTheShortenedSession(): void
    {
        $session = $this->session();
        $set = ExerciseFixture::set($session, $this->at('10:25'), reps: 8, weight: 60.0);
        $this->apply($session, SessionTransition::Finish, '10:30');

        $session->reschedule(null, 10);

        self::assertEquals($this->at('10:10'), $set->loggedAt);
    }

    public function testAbortingRecordsTheEnd(): void
    {
        $session = $this->session();
        $this->apply($session, SessionTransition::Abort, '10:15');

        self::assertSame(SessionStatus::Aborted, $session->getStatus());
        self::assertEquals($this->at('10:15'), $session->getEndedAt());
    }

    public function testItRecordsSetsWhileRunning(): void
    {
        $session = $this->session();
        ExerciseFixture::set($session, $this->at('10:05'), reps: 8, weight: 60.0);
        ExerciseFixture::set($session, $this->at('10:08'), reps: 8, weight: 60.0);

        self::assertCount(2, $session->getSets());
    }

    public function testItRefusesSetsWhilePaused(): void
    {
        $session = $this->session();
        $this->apply($session, SessionTransition::Pause, '10:20');

        $this->expectException(SessionNotRunning::class);
        $this->expectExceptionMessageIsOrContains('paused');

        ExerciseFixture::set($session, $this->at('10:25'), reps: 8, weight: 60.0);
    }

    public function testItAcceptsACorrectionOnceFinished(): void
    {
        $session = $this->session();
        $this->apply($session, SessionTransition::Finish, '11:00');

        ExerciseFixture::set($session, $this->at('11:05'), reps: 8, weight: 60.0);

        self::assertCount(1, $session->getSets());
    }

    public function testEveryTransitionIsConfiguredInTheWorkflow(): void
    {
        $configured = array_values(array_unique(array_map(static fn ($transition): string => $transition->getName(), $this->workflow()->getDefinition()->getTransitions())));
        sort($configured);

        self::assertSame($this->named(...SessionTransition::cases()), $configured);
    }

    /**
     * @return list<string>
     */
    private function named(SessionTransition ...$transitions): array
    {
        $names = array_map(static fn (SessionTransition $transition): string => $transition->value, $transitions);
        sort($names);

        return $names;
    }

    private function apply(WorkoutSession $session, SessionTransition $transition, string $time): void
    {
        $this->workflow()->apply($session, $transition->value, ['at' => $this->at($time)]);
    }

    private function workflow(): WorkflowInterface
    {
        $workflow = self::getContainer()->get('state_machine.session');
        self::assertInstanceOf(WorkflowInterface::class, $workflow);

        return $workflow;
    }

    private function session(): WorkoutSession
    {
        return new WorkoutSession($this->at('10:00'), ExerciseFixture::workout());
    }

    private function at(string $time): DateTimeImmutable
    {
        return new DateTimeImmutable("2026-10-27 {$time}:00");
    }
}
