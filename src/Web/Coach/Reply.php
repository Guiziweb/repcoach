<?php

declare(strict_types=1);

namespace App\Web\Coach;

use App\Web\Coach\Confirmation\PendingCall;

final readonly class Reply
{
    /**
     * @param list<array{name: string, count: int, reads: bool}> $tools
     */
    public function __construct(
        public string $text,
        public array $tools,
        public ?Consumption $consumption,
        public ?string $visit,
        public bool $refresh,
        public ?PendingCall $confirm,
    ) {
    }
}
