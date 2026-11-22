<?php

declare(strict_types=1);

namespace App\Web\Coach\Confirmation;

use App\Session\PerformedSetRepository;
use App\Session\WorkoutSessionRepository;
use App\Shared\Refused;
use App\Web\Coach\ToolNames;
use App\Workout\WorkoutRepository;
use Symfony\AI\Platform\Result\ToolCall;
use Symfony\Contracts\Translation\TranslatorInterface;

final readonly class DestructionInWords
{
    public function __construct(
        private ToolNames $names,
        private WorkoutRepository $workouts,
        private WorkoutSessionRepository $sessions,
        private PerformedSetRepository $sets,
        private TranslatorInterface $translator,
    ) {
    }

    public function of(ToolCall $call): string
    {
        $tool = $this->names->bare($call->getName());
        $arguments = $call->getArguments();

        try {
            $parameters = match ($tool) {
                'delete_workout' => ['workout' => $this->workouts->get(self::text($arguments, 'workout'))->name],
                'remove_exercise' => $this->exercise(self::text($arguments, 'workout'), self::number($arguments, 'position')),
                'delete_session' => $this->session(self::number($arguments, 'sessionId')),
                'remove_set' => $this->set(self::number($arguments, 'setId')),
                'abort_session' => ['workout' => $this->sessions->open()->workoutName ?? ''],
                default => null,
            };
        } catch (Refused) {
            $parameters = null;
        }

        return null === $parameters ? $this->names->shown($call->getName()) : $this->translator->trans('destruction.'.$tool, $parameters);
    }

    /**
     * @return array<string, string>
     */
    private function exercise(string $workout, int $position): array
    {
        $plan = $this->workouts->get($workout);

        return ['exercise' => $plan->line($position)->exercise->name, 'workout' => $plan->name];
    }

    /**
     * @return array<string, mixed>
     */
    private function session(int $id): array
    {
        $session = $this->sessions->get($id);

        return ['workout' => $session->workoutName, 'date' => $session->startedAt];
    }

    /**
     * @return array<string, string>
     */
    private function set(int $id): array
    {
        $set = $this->sets->get($id);
        $amount = null !== $set->reps ? (string) $set->reps : $set->seconds.' s';

        return [
            'effort' => null !== $set->weight ? \sprintf('%s × %s kg', $amount, $set->weight) : $amount,
            'exercise' => $set->exercise->name,
        ];
    }

    /**
     * @param array<mixed> $arguments
     */
    private static function text(array $arguments, string $name): string
    {
        $value = $arguments[$name] ?? null;

        return \is_string($value) ? $value : throw new Refused(\sprintf('The call has no "%s".', $name));
    }

    /**
     * @param array<mixed> $arguments
     */
    private static function number(array $arguments, string $name): int
    {
        $value = $arguments[$name] ?? null;

        return \is_int($value) ? $value : throw new Refused(\sprintf('The call has no "%s".', $name));
    }
}
