<?php

declare(strict_types=1);

namespace App\Web\Coach;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class ConfirmRequest
{
    public function __construct(
        #[Assert\Uuid]
        public string $id,
        #[Assert\Regex(pattern: '#^/#', message: 'The screen is the path of the page on display.')]
        #[Assert\Length(max: 2000)]
        public string $screen = '/',
    ) {
    }
}
