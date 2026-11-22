<?php

declare(strict_types=1);

namespace App\Web\Coach;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\RateLimiter\RateLimiterFactoryInterface;

final readonly class DailyTokens
{
    private const string EVERYONE = 'everyone';

    public function __construct(
        #[Autowire(service: 'limiter.coach_tokens')]
        private RateLimiterFactoryInterface $budget,
    ) {
    }

    public function left(): bool
    {
        return $this->remaining() > 0;
    }

    public function spend(int $tokens): void
    {
        $this->budget->create(self::EVERYONE)->consume(min($tokens, $this->remaining()));
    }

    private function remaining(): int
    {
        return $this->budget->create(self::EVERYONE)->consume(0)->getRemainingTokens();
    }
}
