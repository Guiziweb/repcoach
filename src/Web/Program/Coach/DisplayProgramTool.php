<?php

declare(strict_types=1);

namespace App\Web\Program\Coach;

use App\Web\Coach\Display\Destination;
use App\Workout\WorkoutRepository;
use Symfony\AI\Agent\Toolbox\Attribute\AsTool;
use Symfony\AI\Platform\Tool\ToolEffectHints;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

#[AsTool('display_program', 'Bring a workout onto the screen. Use it whenever the user wants to see a workout, instead of describing it in words.', effectHints: new ToolEffectHints(readOnlyHint: true))]
final readonly class DisplayProgramTool
{
    public function __construct(
        private WorkoutRepository $workouts,
        private UrlGeneratorInterface $urls,
        private Destination $destination,
    ) {
    }

    /**
     * @param string $name name of the workout, as returned by list_workouts
     */
    public function __invoke(string $name): string
    {
        $workout = $this->workouts->named($name);

        if (null === $workout) {
            return \sprintf('There is no workout named "%s". Use list_workouts to see them.', $name);
        }

        $this->destination->go($this->urls->generate('program', ['name' => $workout->name]));

        return \sprintf('The workout "%s" is now on screen.', $workout->name);
    }
}
