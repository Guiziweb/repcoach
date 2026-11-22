<?php

declare(strict_types=1);

namespace App\Web\Coach;

use Symfony\AI\Agent\Toolbox\Event\ToolCallSucceeded;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Contracts\Service\ResetInterface;

#[AsEventListener]
final class ToolTrail implements ResetInterface
{
    /**
     * @var array<string, array{count: int, reads: bool}>
     */
    private array $calls = [];

    public function __construct(
        private readonly ToolNames $names,
    ) {
    }

    public function __invoke(ToolCallSucceeded $event): void
    {
        $tool = $event->getDefinition();
        $name = $this->names->shown($tool->getName());

        $this->calls[$name] = ['count' => ($this->calls[$name]['count'] ?? 0) + 1, 'reads' => $tool->isReadOnly()];
    }

    /**
     * @return list<array{name: string, count: int, reads: bool}>
     */
    public function summary(): array
    {
        return array_map(
            static fn (string $name, array $call): array => ['name' => $name, ...$call],
            array_keys($this->calls),
            array_values($this->calls),
        );
    }

    public function wrote(): bool
    {
        return array_any($this->calls, static fn (array $call): bool => !$call['reads']);
    }

    public function reset(): void
    {
        $this->calls = [];
    }
}
