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
        yield 'line break' => ["Arms\nLegs", 'fits on one line'];
        yield 'tab' => ["Arms\tLegs", 'fits on one line'];
        yield 'dot' => ['.', 'cannot have'];
        yield 'two dots' => ['..', 'cannot have'];
        yield 'two dots between slashes' => ['Arms/../Legs', 'cannot have'];
        yield 'dot after a slash' => ['Arms/.', 'cannot have'];
    }

    #[DataProvider('acceptedNames')]
    public function testItAcceptsAName(string $name): void
    {
        Checker::make()(new WorkoutName($name));

        $this->addToAssertionCount(1);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function acceptedNames(): iterable
    {
        yield 'slash' => ['Push/Pull'];
        yield 'dots in a word' => ['A.B.C'];
        yield 'ellipsis' => ['Legs...'];
        yield 'emoji' => ['🏋️ Legs'];
        yield 'markup' => ['<b>Pecs</b>'];
    }

    public function testItKeepsTheNameWithoutTheSpacesAroundIt(): void
    {
        self::assertSame('Pecs', (new WorkoutName('  Pecs '))->value);
    }
}
