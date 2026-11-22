<?php

declare(strict_types=1);

namespace App\Web\Coach\Confirmation;

use Symfony\AI\Agent\Toolbox\Event\ToolCallRequested;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

#[AsEventListener]
final readonly class ConfirmsDestructiveTools
{
    public function __construct(
        private Approvals $approvals,
        private DestructionInWords $words,
    ) {
    }

    public function __invoke(ToolCallRequested $event): void
    {
        $call = $event->getToolCall();

        if (!$event->getDefinition()->isDestructive() || $this->approvals->consume($call)) {
            return;
        }

        $this->approvals->ask(PendingCall::of($call, $this->words->of($call)));
        $event->deny('Not done yet: the user has to confirm it by pressing the button now shown under your answer. Tell them in one short sentence to press it if they agree, and do not call this tool again before they do.');
    }
}
