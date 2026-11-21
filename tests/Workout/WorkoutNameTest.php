<?php

declare(strict_types=1);

namespace App\Tests\Workout;

use App\Shared\Limits;
use App\Shared\Refused;
use App\Tests\Checker;
use App\Workout\WorkoutName;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class WorkoutNameTest extends TestCase
{
    #[DataProvider('refusedNames')]
    public function testItRefusesANameNoOneCouldUse(string $name, string $reason): void
    {
        $this->expectException(Refused::class);
        $this->expectExceptionMessageIsOrContains($reason);

        Checker::make()(new WorkoutName($name));
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function refusedNames(): iterable
    {
        yield 'empty' => ['', 'needs a name'];
        yield 'only spaces' => ['   ', 'needs a name'];
        yield 'too long' => [str_repeat('a', Limits::MAX_NAME + 1), 'at most 120 characters'];
    }

    public function testItKeepsTheNameWithoutTheSpacesAroundIt(): void
    {
        self::assertSame('Pecs', (new WorkoutName('  Pecs '))->value);
    }
}
