<?php

declare(strict_types=1);

namespace App\Web\Menu;

use App\Web\Coach\Screen\DescribesScreen;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;

#[AsTaggedItem('menu')]
final readonly class MenuDescription implements DescribesScreen
{
    public function describe(array $parameters): string
    {
        return 'The menu. Nothing is on screen.';
    }
}
