<?php

declare(strict_types=1);

namespace App\Tests\Web\Coach\Screen;

use App\Web\Coach\Screen\EffortInWords;
use PHPUnit\Framework\TestCase;

final class EffortInWordsTest extends TestCase
{
    public function testItSaysTheRepsAndTheLoad(): void
    {
        self::assertSame('12 reps at 8.5 kg', EffortInWords::of(12, null, 8.5));
    }

    public function testItSaysTheRepsOfABodyweightSet(): void
    {
        self::assertSame('8 reps', EffortInWords::of(8, null, null));
    }

    public function testItSaysTheSecondsOfATimedSet(): void
    {
        self::assertSame('60 seconds', EffortInWords::of(null, 60, null));
    }
}
