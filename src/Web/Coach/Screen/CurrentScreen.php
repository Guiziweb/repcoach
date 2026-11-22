<?php

declare(strict_types=1);

namespace App\Web\Coach\Screen;

use Symfony\Contracts\Service\ResetInterface;

final class CurrentScreen implements ResetInterface
{
    private string $url = '/';

    public function show(string $url): void
    {
        $this->url = $url;
    }

    public function url(): string
    {
        return $this->url;
    }

    public function reset(): void
    {
        $this->url = '/';
    }
}
