<?php

declare(strict_types=1);

namespace App\Web\Session\Coach;

use App\Web\Coach\Screen\DescribesScreen;
use App\Web\Session\SessionPages;
use App\Web\Session\View\SessionView;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;

#[AsTaggedItem('session')]
final readonly class SessionDescription implements DescribesScreen
{
    public function __construct(
        private SessionPages $pages,
    ) {
    }

    public function describe(array $parameters): string
    {
        $id = $parameters['id'] ?? null;

        return self::of(is_numeric($id) ? $this->pages->session((int) $id) : null);
    }

    public static function of(?SessionView $view): string
    {
        if (null === $view) {
            return 'A session that no longer exists.';
        }

        $session = $view->session;
        $lines = [];

        foreach ($view->exercises as $exercise => $sets) {
            $lines[] = \sprintf('%d. %s: %s', \count($lines) + 1, $exercise, SetsInWords::of($sets));
        }

        return \sprintf(
            "Session %d, workout \"%s\", started %s, lasted %d minutes:\n%s",
            $session->getId(),
            $session->workoutName,
            $session->startedAt->format('D j M Y H:i'),
            $view->duration->minutes(),
            implode("\n", $lines),
        );
    }
}
