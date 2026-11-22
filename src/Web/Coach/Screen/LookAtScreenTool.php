<?php

declare(strict_types=1);

namespace App\Web\Coach\Screen;

use Symfony\AI\Agent\Toolbox\Attribute\AsTool;
use Symfony\AI\Platform\Tool\ToolEffectHints;

#[AsTool('look_at_screen', 'What the screen next to you shows right now, as written by the app. Call it when the user refers to what is on screen.', effectHints: new ToolEffectHints(readOnlyHint: true))]
final readonly class LookAtScreenTool
{
    public function __construct(
        private Screens $screens,
        private CurrentScreen $screen,
    ) {
    }

    public function __invoke(): string
    {
        $description = $this->screens->describe($this->screen->url());

        return json_encode(['on_screen' => '' === $description ? 'Nothing the coach can read.' : $description], \JSON_THROW_ON_ERROR | \JSON_UNESCAPED_UNICODE);
    }
}
