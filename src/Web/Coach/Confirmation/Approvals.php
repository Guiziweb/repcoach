<?php

declare(strict_types=1);

namespace App\Web\Coach\Confirmation;

use Symfony\AI\Platform\Result\ToolCall;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\Service\ResetInterface;

final class Approvals implements ResetInterface
{
    private const string PENDING = 'coach_pending_call';

    private ?string $granted = null;

    public function __construct(
        private readonly RequestStack $requests,
    ) {
    }

    public function ask(PendingCall $call): void
    {
        $this->requests->getSession()->set(self::PENDING, $call);
    }

    public function pending(): ?PendingCall
    {
        $call = $this->requests->getSession()->get(self::PENDING);

        return $call instanceof PendingCall ? $call : null;
    }

    public function withdraw(): void
    {
        $this->requests->getSession()->remove(self::PENDING);
    }

    public function grant(string $id): ?PendingCall
    {
        $call = $this->pending();

        if (null === $call || $call->id !== $id) {
            return null;
        }

        $this->withdraw();
        $this->granted = $call->fingerprint();

        return $call;
    }

    public function consume(ToolCall $call): bool
    {
        if (null === $this->granted || $this->granted !== PendingCall::fingerprintOf($call)) {
            return false;
        }

        $this->granted = null;

        return true;
    }

    public function reset(): void
    {
        $this->granted = null;
    }
}
