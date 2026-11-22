<?php

declare(strict_types=1);

namespace App\Tests\Browser;

use Playwright\Page\PageInterface;

trait HasSeenTheHelp
{
    abstract protected function getPage(): PageInterface;

    private function skipTheHelp(): void
    {
        $this->getPage()->addInitScript("localStorage.setItem('repcoach.help', 'seen')");
    }
}
