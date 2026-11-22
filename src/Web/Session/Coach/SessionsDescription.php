<?php

declare(strict_types=1);

namespace App\Web\Session\Coach;

use App\Web\Coach\Screen\DescribesScreen;
use App\Web\Session\SessionPages;
use App\Web\Session\View\SessionRow;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;

#[AsTaggedItem('sessions')]
final readonly class SessionsDescription implements DescribesScreen
{
    public function __construct(
        private SessionPages $pages,
    ) {
    }

    public function describe(array $parameters): string
    {
        $number = $parameters['page'] ?? null;
        $page = $this->pages->page(is_numeric($number) ? (int) $number : 1);

        return self::of(iterator_to_array($page->getCurrentPageResults(), false), $page->getCurrentPage(), $page->getNbPages());
    }

    /**
     * @param list<SessionRow> $rows
     */
    public static function of(array $rows, int $page, int $pages): string
    {
        if ([] === $rows) {
            return 'The list of finished sessions, empty.';
        }

        return \sprintf("The list of finished sessions, page %d of %d:\n%s", $page, $pages, implode("\n", array_map(self::line(...), $rows)));
    }

    private static function line(SessionRow $row): string
    {
        $session = $row->tally->session;

        return \sprintf(
            'Session %d: %s on %s, %d minutes, %d sets, %s kg',
            $session->getId(),
            $session->workoutName,
            $session->startedAt->format('D j M Y H:i'),
            $row->duration->minutes(),
            $row->tally->sets,
            $row->tally->volume,
        );
    }
}
