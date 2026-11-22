<?php

declare(strict_types=1);

namespace App\Web\Language;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\RequestEvent;

#[AsEventListener(priority: 20)]
final readonly class RemembersTheLanguage
{
    public const string CHOSEN = 'language';

    public function __invoke(RequestEvent $event): void
    {
        $request = $event->getRequest();

        if (!$request->hasPreviousSession() || $request->attributes->has('_locale')) {
            return;
        }

        $language = $request->getSession()->get(self::CHOSEN);

        if (\is_string($language)) {
            $request->attributes->set('_locale', $language);
        }
    }
}
