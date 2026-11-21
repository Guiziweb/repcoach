<?php

declare(strict_types=1);

namespace App\Tests\Session;

use App\Session\SessionStatus;
use App\Session\WorkoutSession;
use App\Tests\ExerciseFixture;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(WorkoutSession::class)]
final class WorkoutSessionTest extends TestCase
{
    public function testItStartsRunning(): void
    {
        self::assertSame(SessionStatus::Running, $this->session()->getStatus());
    }

    public function testItRecordsSetsWhileRunning(): void
    {
        $session = $this->session();
        ExerciseFixture::set($session, $this->at('10:05'), reps: 8, weight: 60.0);
        ExerciseFixture::set($session, $this->at('10:08'), reps: 8, weight: 60.0);

        self::assertCount(2, $session->getSets());
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
