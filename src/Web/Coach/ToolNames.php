<?php

declare(strict_types=1);

namespace App\Web\Coach;

use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class ToolNames
{
    public function __construct(
        #[Autowire(param: 'app.coach.tool_prefix')]
        private string $prefix,
    ) {
    }

    public function bare(string $tool): string
    {
        return str_starts_with($tool, $this->prefix) ? substr($tool, \strlen($this->prefix)) : $tool;
    }

    public function shown(string $tool): string
    {
        return str_replace('_', ' ', $this->bare($tool));
    }
}
