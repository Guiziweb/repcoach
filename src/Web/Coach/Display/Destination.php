<?php

declare(strict_types=1);

namespace App\Web\Coach\Display;

use Symfony\Contracts\Service\ResetInterface;

final class Destination implements ResetInterface
{
    private ?string $url = null;

    public function go(string $url): void
    {
        $this->url = $url;
    }

    public function url(): ?string
    {
        return $this->url;
    }

    public function reset(): void
    {
        $this->url = null;
    }
}
