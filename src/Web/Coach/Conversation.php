<?php

declare(strict_types=1);

namespace App\Web\Coach;

use Symfony\AI\Agent\MessageStore\MessageStoreInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Uid\UuidV7;

final readonly class Conversation
{
    private const string ID = 'coach_conversation_id';

    public function __construct(
        private RequestStack $requests,
        private MessageStoreInterface $coach,
    ) {
    }

    public function id(): UuidV7
    {
        $session = $this->requests->getSession();
        $id = $session->get(self::ID);

        if (\is_string($id) && UuidV7::isValid($id)) {
            return UuidV7::fromString($id);
        }

        $fresh = new UuidV7();
        $session->set(self::ID, $fresh->toRfc4122());

        return $fresh;
    }

    public function forget(): void
    {
        $this->coach->delete($this->id());
        $this->requests->getSession()->remove(self::ID);
    }
}
