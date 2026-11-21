<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Session\PerformedSet;
use App\Session\SessionTransition;
use App\Session\WorkoutSession;
use App\Workout\Workout;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Psr\Clock\ClockInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Component\Workflow\WorkflowInterface;

final class HistoryFixtures extends Fixture implements DependentFixtureInterface
{
    /**
     * @param list<array{program: string, daysAgo: int, at: string, minutes: int, done: array<string, array{reps?: int, seconds?: int, weight?: int|float}>}> $sessions
     */
    public function __construct(
        #[Autowire('%app.sessions%')]
        private readonly array $sessions,
        #[Target('session')]
        private readonly WorkflowInterface $workflow,
        private readonly ClockInterface $clock,
    ) {
    }

    public function getDependencies(): array
    {
        return [ProgramFixtures::class];
    }

    public function load(ObjectManager $manager): void
    {
        $workouts = [];

        foreach ($manager->getRepository(Workout::class)->findAll() as $workout) {
            $workouts[$workout->name] = $workout;
        }

        foreach ($this->sessions as $performed) {
            $start = $this->clock->now()->modify(\sprintf('-%d days %s', $performed['daysAgo'], $performed['at']));
            $session = new WorkoutSession($start, $workouts[$performed['program']]);

            foreach ($session->getPlan() as $line) {
                $result = $performed['done'][$line->exercise->id];

                for ($set = 0; $set < $line->sets; ++$set) {
                    new PerformedSet($session, $line->exercise, $line, $start, $result['reps'] ?? null, $result['seconds'] ?? null, $result['weight'] ?? null);
                }
            }

            $this->workflow->apply($session, SessionTransition::Finish->value, ['at' => $start->modify(\sprintf('+%d minutes', $performed['minutes']))]);
            $manager->persist($session);
        }

        $manager->flush();
    }
}
