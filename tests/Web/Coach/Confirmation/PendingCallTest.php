<?php

declare(strict_types=1);

namespace App\Tests\Web\Coach\Confirmation;

use App\Web\Coach\Confirmation\PendingCall;
use PHPUnit\Framework\TestCase;
use Symfony\AI\Platform\Result\ToolCall;

final class PendingCallTest extends TestCase
{
    public function testItReplaysTheCallItWasMadeOf(): void
    {
        $pending = PendingCall::of(new ToolCall('toolu_1', 'repcoach_delete_workout', ['workout' => 'Pecs']), 'delete the workout');

        self::assertSame('repcoach_delete_workout', $pending->call()->getName());
        self::assertSame(['workout' => 'Pecs'], $pending->call()->getArguments());
        self::assertSame('delete the workout', $pending->action);
    }

    public function testTheSameCallHasTheSameFingerprintWhateverTheOrderOfItsArguments(): void
    {
        $first = new ToolCall('toolu_1', 'repcoach_remove_exercise', ['workout' => 'Pecs', 'position' => 2]);
        $second = new ToolCall('toolu_2', 'repcoach_remove_exercise', ['position' => 2, 'workout' => 'Pecs']);

        self::assertSame(PendingCall::fingerprintOf($first), PendingCall::fingerprintOf($second));
    }

    public function testAnotherArgumentMakesAnotherCall(): void
    {
        $pecs = new ToolCall('toolu_1', 'repcoach_delete_workout', ['workout' => 'Pecs']);
        $legs = new ToolCall('toolu_1', 'repcoach_delete_workout', ['workout' => 'Legs']);

        self::assertNotSame(PendingCall::fingerprintOf($pecs), PendingCall::fingerprintOf($legs));
    }

    public function testEachPendingCallGetsItsOwnId(): void
    {
        $call = new ToolCall('toolu_1', 'repcoach_delete_workout', ['workout' => 'Pecs']);

        self::assertNotSame(PendingCall::of($call, 'delete')->id, PendingCall::of($call, 'delete')->id);
    }
}
