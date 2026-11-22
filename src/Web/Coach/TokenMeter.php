<?php

declare(strict_types=1);

namespace App\Web\Coach;

use Symfony\AI\Platform\TokenUsage\TokenUsageAggregation;
use Symfony\AI\Platform\TokenUsage\TokenUsageInterface;
use Symfony\Component\HttpFoundation\RequestStack;

final readonly class TokenMeter
{
    private const string TOTAL = 'coach_tokens';

    private const string CONTEXT = 'coach_context';

    public function __construct(
        private RequestStack $requests,
    ) {
    }

    public function measure(mixed $usage): ?Consumption
    {
        if (!$usage instanceof TokenUsageInterface) {
            return null;
        }

        $calls = $usage instanceof TokenUsageAggregation ? $usage->getTokenUsages() : [$usage];
        $last = end($calls);
        $reply = self::read($usage) + ($usage->getCompletionTokens() ?? 0);
        $context = false === $last ? 0 : self::read($last);
        $this->requests->getSession()->set(self::CONTEXT, $context);

        return new Consumption($context, $reply, $usage->getCacheReadTokens() ?? 0, $this->add($reply));
    }

    public function context(): int
    {
        $context = $this->requests->getSession()->get(self::CONTEXT, 0);

        return \is_int($context) ? $context : 0;
    }

    public function reset(): void
    {
        $this->requests->getSession()->remove(self::TOTAL);
        $this->requests->getSession()->remove(self::CONTEXT);
    }

    private function add(int $tokens): int
    {
        $session = $this->requests->getSession();
        $total = $session->get(self::TOTAL, 0);
        $total = (\is_int($total) ? $total : 0) + $tokens;
        $session->set(self::TOTAL, $total);

        return $total;
    }

    private static function read(TokenUsageInterface $usage): int
    {
        return ($usage->getPromptTokens() ?? 0) + ($usage->getCacheReadTokens() ?? 0) + ($usage->getCacheCreationTokens() ?? 0);
    }
}
