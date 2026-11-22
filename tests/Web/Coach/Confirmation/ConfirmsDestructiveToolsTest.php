<?php

declare(strict_types=1);

namespace App\Tests\Web\Coach\Confirmation;

use App\Tests\ExerciseFixture;
use App\Tests\ResetsTheDatabase;
use App\Web\Coach\Confirmation\Approvals;
use App\Web\Coach\Confirmation\ConfirmsDestructiveTools;
use Symfony\AI\Agent\Toolbox\Event\ToolCallRequested;
use Symfony\AI\Platform\Result\ToolCall;
use Symfony\AI\Platform\Tool\ExecutionReference;
use Symfony\AI\Platform\Tool\Tool;
use Symfony\AI\Platform\Tool\ToolEffectHints;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

final class ConfirmsDestructiveToolsTest extends KernelTestCase
{
    use ResetsTheDatabase;

    private ConfirmsDestructiveTools $listener;

    private Approvals $approvals;

    protected function setUp(): void
    {
        self::bootKernel();
        $entityManager = $this->emptyDatabase();

        $exercise = ExerciseFixture::exercise();
        $entityManager->persist($exercise);
        $entityManager->persist(ExerciseFixture::workout('Pecs', $exercise));
        $entityManager->flush();

        $request = new Request();
        $request->setSession(new Session(new MockArraySessionStorage()));
        $requests = self::getContainer()->get(RequestStack::class);
        self::assertInstanceOf(RequestStack::class, $requests);
        $requests->push($request);

        $listener = self::getContainer()->get(ConfirmsDestructiveTools::class);
        $approvals = self::getContainer()->get(Approvals::class);
        self::assertInstanceOf(ConfirmsDestructiveTools::class, $listener);
        self::assertInstanceOf(Approvals::class, $approvals);
        $this->listener = $listener;
        $this->approvals = $approvals;
    }

    public function testADestructiveToolWaitsForTheButton(): void
    {
        $event = self::requested('repcoach_delete_workout', ['workout' => 'Pecs'], destructive: true);

        ($this->listener)($event);

        self::assertTrue($event->isDenied());
        self::assertSame('delete the workout “Pecs”', $this->approvals->pending()?->action);
    }

    public function testAToolThatChangesNothingRunsAtOnce(): void
    {
        $event = self::requested('repcoach_list_workouts', [], destructive: false, readOnly: true);

        ($this->listener)($event);

        self::assertFalse($event->isDenied());
        self::assertNull($this->approvals->pending());
    }

    public function testAWriteThatDestroysNothingRunsAtOnce(): void
    {
        $event = self::requested('repcoach_rename_workout', ['workout' => 'Pecs', 'name' => 'Chest'], destructive: false);

        ($this->listener)($event);

        self::assertFalse($event->isDenied());
    }

    public function testTheConfirmedCallRunsOnce(): void
    {
        ($this->listener)(self::requested('repcoach_delete_workout', ['workout' => 'Pecs'], destructive: true));
        $this->approvals->grant((string) $this->approvals->pending()?->id);

        $confirmed = self::requested('repcoach_delete_workout', ['workout' => 'Pecs'], destructive: true);
        ($this->listener)($confirmed);
        $again = self::requested('repcoach_delete_workout', ['workout' => 'Pecs'], destructive: true);
        ($this->listener)($again);

        self::assertFalse($confirmed->isDenied());
        self::assertTrue($again->isDenied());
    }

    public function testAnUnknownTargetIsNamedByItsTool(): void
    {
        ($this->listener)(self::requested('repcoach_delete_workout', ['workout' => 'Legs'], destructive: true));

        self::assertSame('delete workout', $this->approvals->pending()?->action);
    }

    /**
     * @param array<string, mixed> $arguments
     */
    private static function requested(string $name, array $arguments, bool $destructive, bool $readOnly = false): ToolCallRequested
    {
        $definition = new Tool(
            new ExecutionReference(self::class),
            $name,
            'A tool.',
            effectHints: new ToolEffectHints(readOnlyHint: $readOnly, destructiveHint: $destructive),
        );

        return new ToolCallRequested(new ToolCall('toolu_1', $name, $arguments), $definition);
    }
}
