<?php

declare(strict_types=1);

namespace App\Web\Coach;

use App\Web\Coach\Display\Destination;
use App\Web\Coach\Screen\CurrentScreen;
use Psr\Log\LoggerInterface;
use Symfony\AI\Agent\AgentInterface;
use Symfony\AI\Platform\Message\Message;
use Symfony\AI\Platform\Message\MessageBag;
use Symfony\AI\Platform\Message\MessageInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Throwable;

final readonly class Coach
{
    public function __construct(
        private AgentInterface $coach,
        private CurrentScreen $screen,
        private ToolTrail $trail,
        private Conversation $conversation,
        private TokenMeter $meter,
        private TranslatorInterface $translator,
        private Destination $destination,
        private LoggerInterface $logger,
    ) {
    }

    public function reply(string $message, string $screen): Reply
    {
        return $this->answer($screen, Message::ofUser($message));
    }

    private function answer(string $screen, MessageInterface $message): Reply
    {
        $this->screen->show($screen);

        try {
            $execution = $this->coach->call(MessageBag::forId($this->conversation->id(), $message));
            $text = $execution->asText();
            $consumption = $this->meter->measure($execution->getResult()->getMetadata()->get('token_usage'));
        } catch (Throwable $failure) {
            $this->logger->error('The coach could not answer.', ['exception' => $failure]);
            $text = $this->translator->trans('buddy.failed');
            $consumption = null;
        }

        $visit = $this->destination->url();

        return new Reply($text, $this->trail->summary(), $consumption, $visit, null === $visit && $this->trail->wrote());
    }

    public function forget(): void
    {
        $this->conversation->forget();
        $this->meter->reset();
    }
}
