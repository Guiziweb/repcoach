<?php

declare(strict_types=1);

namespace App\Web\Coach\Confirmation;

use Symfony\AI\Platform\Result\ToolCall;
use Symfony\Component\Uid\Uuid;

final readonly class PendingCall
{
    /**
     * @param array<string, mixed> $arguments
     */
    private function __construct(
        public string $id,
        public string $action,
        public string $tool,
        public array $arguments,
    ) {
    }

    public static function of(ToolCall $call, string $action): self
    {
        return new self(
            Uuid::v4()->toRfc4122(),
            $action,
            $call->getName(),
            $call->getArguments(),
        );
    }

    public function call(): ToolCall
    {
        return new ToolCall($this->id, $this->tool, $this->arguments);
    }

    public function fingerprint(): string
    {
        return self::fingerprintOf($this->call());
    }

    public static function fingerprintOf(ToolCall $call): string
    {
        $arguments = $call->getArguments();
        ksort($arguments);

        return hash('sha256', $call->getName().json_encode($arguments, \JSON_THROW_ON_ERROR));
    }
}
