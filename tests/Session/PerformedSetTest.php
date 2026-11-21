<?php

declare(strict_types=1);

namespace App\Tests\Session;

use App\Session\PerformedSet;
use App\Session\WorkoutSession;
use App\Tests\ExerciseFixture;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PerformedSet::class)]
final class PerformedSetTest extends TestCase
{
    public function testVolumeIsRepetitionsTimesWeight(): void
    {
        $set = ExerciseFixture::set($this->session(), $this->at(), reps: 8, weight: 60.0);

        self::assertSame(480.0, $set->volume());
    }

    public function testABodyweightSetHasNoVolume(): void
    {
        $set = ExerciseFixture::set($this->session(), $this->at(), reps: 12);

        self::assertSame(0.0, $set->volume());
    }

    public function testAHoldIsMeasuredInSeconds(): void
    {
        $set = ExerciseFixture::set($this->session(), $this->at(), seconds: 45);

        self::assertSame(45, $set->seconds);
        self::assertNull($set->reps);
    }

    private function session(): WorkoutSession
    {
        return new WorkoutSession($this->at(), ExerciseFixture::workout());
    }

    private function at(): DateTimeImmutable
    {
        return new DateTimeImmutable('2026-10-27 10:00:00');
    }
}
