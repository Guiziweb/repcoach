<?php

declare(strict_types=1);

namespace App\Web\Coach;

use Symfony\AI\Platform\Event\ResultConvertedEvent;
use Symfony\AI\Platform\TokenUsage\TokenUsageInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

#[AsEventListener]
final readonly class ChargesTheDailyBudget
{
    public function __construct(
        private DailyTokens $daily,
    ) {
    }

    public function __invoke(ResultConvertedEvent $event): void
    {
        $usage = $event->getResult()->getMetadata()->get('token_usage');

        if (!$usage instanceof TokenUsageInterface) {
            return;
        }

        $this->daily->spend(
            ($usage->getPromptTokens() ?? 0)
            + ($usage->getCacheReadTokens() ?? 0)
            + ($usage->getCacheCreationTokens() ?? 0)
            + ($usage->getCompletionTokens() ?? 0),
        );
    }
}
