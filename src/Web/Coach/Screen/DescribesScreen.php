<?php

declare(strict_types=1);

namespace App\Web\Coach\Screen;

use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag]
interface DescribesScreen
{
    /**
     * @param array<mixed> $parameters the route attributes and the query of the page on screen
     */
    public function describe(array $parameters): string;
}
