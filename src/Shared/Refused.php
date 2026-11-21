<?php

declare(strict_types=1);

namespace App\Shared;

use InvalidArgumentException;

class Refused extends InvalidArgumentException
{
    public function __construct(string $message, public readonly ?Reason $reason = null)
    {
        parent::__construct($message);
    }
}
