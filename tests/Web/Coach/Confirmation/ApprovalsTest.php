<?php

declare(strict_types=1);

namespace App\Tests\Web\Coach\Confirmation;

use App\Web\Coach\Confirmation\Approvals;
use App\Web\Coach\Confirmation\PendingCall;
use PHPUnit\Framework\TestCase;
use Symfony\AI\Platform\Result\ToolCall;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

final class ApprovalsTest extends TestCase
{
    private Approvals $approvals;

    private ToolCall $deletion;

    protected function setUp(): void
    {
        $request = new Request();
        $request->setSession(new Session(new MockArraySessionStorage()));
        $requests = new RequestStack();
        $requests->push($request);

        $this->approvals = new Approvals($requests);
        $this->deletion = new ToolCall('toolu_1', 'repcoach_delete_workout', ['workout' => 'Pecs']);
    }

    public function testNothingIsAllowedBeforeTheUserConfirms(): void
    {
        $this->approvals->ask(PendingCall::of($this->deletion, 'delete Pecs'));

        self::assertFalse($this->approvals->consume($this->deletion));
    }

    public function testTheConfirmedCallIsAllowedOnce(): void
    {
        $pending = PendingCall::of($this->deletion, 'delete Pecs');
        $this->approvals->ask($pending);

        self::assertSame($pending->id, $this->approvals->grant($pending->id)?->id);
        self::assertTrue($this->approvals->consume($this->deletion));
        self::assertFalse($this->approvals->consume($this->deletion));
    }

    public function testAConfirmationOnlyAllowsTheCallItWasShownFor(): void
    {
        $pending = PendingCall::of($this->deletion, 'delete Pecs');
        $this->approvals->ask($pending);
        $this->approvals->grant($pending->id);

        self::assertFalse($this->approvals->consume(new ToolCall('toolu_2', 'repcoach_delete_workout', ['workout' => 'Legs'])));
    }

    public function testAnUnknownConfirmationGrantsNothing(): void
    {
        $this->approvals->ask(PendingCall::of($this->deletion, 'delete Pecs'));

        self::assertNull($this->approvals->grant('not-the-id'));
        self::assertFalse($this->approvals->consume($this->deletion));
    }

    public function testAWithdrawnCallCanNoLongerBeConfirmed(): void
    {
        $pending = PendingCall::of($this->deletion, 'delete Pecs');
        $this->approvals->ask($pending);
        $this->approvals->withdraw();

        self::assertNull($this->approvals->pending());
        self::assertNull($this->approvals->grant($pending->id));
    }

    public function testAGrantDoesNotOutliveTheRequest(): void
    {
        $pending = PendingCall::of($this->deletion, 'delete Pecs');
        $this->approvals->ask($pending);
        $this->approvals->grant($pending->id);
        $this->approvals->reset();

        self::assertFalse($this->approvals->consume($this->deletion));
    }
}
