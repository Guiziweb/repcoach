<?php

declare(strict_types=1);

namespace App\Web\Coach;

use App\Web\Coach\Confirmation\Approvals;
use App\Web\Coach\Display\Destination;
use App\Web\Coach\Screen\CurrentScreen;
use Psr\Log\LoggerInterface;
use Symfony\AI\Agent\AgentInterface;
use Symfony\AI\Agent\Toolbox\ToolboxInterface;
use Symfony\AI\Agent\Toolbox\ToolResultConverter;
use Symfony\AI\Platform\Message\Message;
use Symfony\AI\Platform\Message\MessageBag;
use Symfony\AI\Platform\Message\MessageInterface;
use Symfony\AI\Platform\Result\ToolCallResult;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\Translation\TranslatorInterface;
use Throwable;

final readonly class Coach
{
    public function __construct(
        private AgentInterface $coach,
        private CurrentScreen $screen,
        private ToolTrail $trail,
        private Approvals $approvals,
        #[Autowire(service: 'ai.toolbox.coach')]
        private ToolboxInterface $toolbox,
        private Conversation $conversation,
        private TokenMeter $meter,
        private TranslatorInterface $translator,
        private Destination $destination,
        private LoggerInterface $logger,
        private DailyTokens $daily,
    ) {
    }

    public function reply(string $message, string $screen): Reply
    {
        $pause = $this->pause();

        if (null !== $pause) {
            return $pause;
        }

        if (!$this->meter->full()) {
            return $this->answer($screen, Message::ofUser($message));
        }

        $this->forget();

        return $this->answer($screen, Message::ofUser($message))->startingWith($this->translator->trans('buddy.fresh'));
    }

    public function confirm(string $id, string $screen): Reply
    {
        $pause = $this->pause();

        if (null !== $pause) {
            return $pause;
        }

        $pending = $this->approvals->grant($id);

        if (null === $pending) {
            return new Reply($this->translator->trans('buddy.expired'), [], null, null, false, null);
        }

        $call = $pending->call();

        try {
            $outcome = new ToolResultConverter()->convert($this->toolbox->execute($call)) ?? '';
        } catch (Throwable $failure) {
            $this->logger->error('The confirmed tool call failed.', ['exception' => $failure]);

            return new Reply($this->translator->trans('buddy.failed'), [], null, null, false, null);
        }

        return $this->answer(
            $screen,
            Message::ofUser($this->translator->trans('buddy.confirmed')),
            Message::ofAssistant(new ToolCallResult([$call])),
            Message::ofToolCall($call, $outcome),
        );
    }

    private function answer(string $screen, MessageInterface ...$messages): Reply
    {
        $this->screen->show($screen);
        $this->approvals->withdraw();

        try {
            $execution = $this->coach->call(MessageBag::forId($this->conversation->id(), ...$messages));
            $text = $execution->asText();
            $consumption = $this->meter->measure($execution->getResult()->getMetadata()->get('token_usage'));
        } catch (Throwable $failure) {
            $this->logger->error('The coach could not answer.', ['exception' => $failure]);
            $this->forget();
            $text = $this->translator->trans('buddy.failed');
            $consumption = null;
        }

        $visit = $this->destination->url();

        return new Reply($text, $this->trail->summary(), $consumption, $visit, null === $visit && $this->trail->wrote(), $this->approvals->pending());
    }

    private function pause(): ?Reply
    {
        if ($this->daily->left()) {
            return null;
        }

        return new Reply($this->translator->trans('buddy.resting'), [], null, null, false, null);
    }

    public function forget(): void
    {
        $this->conversation->forget();
        $this->meter->reset();
        $this->approvals->withdraw();
    }
}
