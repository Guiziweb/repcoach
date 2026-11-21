<?php

declare(strict_types=1);

namespace App\Tests\Mcp;

use App\Tests\ExerciseFixture;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use RuntimeException;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class ServerTest extends WebTestCase
{
    private McpClient $client;

    /**
     * @var list<string>
     */
    private array $called = [];

    protected function setUp(): void
    {
        $browser = self::createClient();
        $this->client = new McpClient($browser);

        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        self::assertInstanceOf(EntityManagerInterface::class, $entityManager);

        $metadata = $entityManager->getMetadataFactory()->getAllMetadata();
        $schema = new SchemaTool($entityManager);
        $schema->dropSchema($metadata);
        $schema->createSchema($metadata);

        $bench = ExerciseFixture::exercise();
        $entityManager->persist($bench);
        $entityManager->persist(ExerciseFixture::exercise(id: 'plank', name: 'Plank', bodyweight: true));
        $entityManager->persist(ExerciseFixture::workout(exercise: $bench));
        $entityManager->flush();

        $this->client->connect();
    }

    public function testEveryToolAnswersWithItsDeclaredShape(): void
    {
        $this->search();
        $this->planAWorkout();
        $this->runASession();
        $this->correctHistory();

        $exposed = array_map(static fn (array $tool): mixed => $tool['name'], $this->client->tools());
        sort($exposed);
        $called = array_values(array_unique($this->called));
        sort($called);

        self::assertSame($exposed, $called, 'Every exposed tool must be exercised here.');
    }

    public function testEveryToolDescribesItsOutput(): void
    {
        foreach ($this->client->tools() as $tool) {
            self::assertArrayHasKey('outputSchema', $tool, \sprintf('%s has no outputSchema', \is_string($tool['name']) ? $tool['name'] : '?'));
        }
    }

    public function testEveryToolRefusesArgumentsItDoesNotKnow(): void
    {
        foreach ($this->client->tools() as $tool) {
            $schema = $tool['inputSchema'] ?? null;
            self::assertIsArray($schema);
            self::assertFalse($schema['additionalProperties'] ?? null, \sprintf('%s accepts unknown arguments', \is_string($tool['name']) ? $tool['name'] : '?'));
        }
    }

    public function testAnUnknownArgumentIsRefusedWithItsName(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageIsOrContains('`colour`');

        $this->client->call('search_exercises', ['colour' => 'red']);
    }

    public function testASearchWithoutAnyWordIsRefused(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageIsOrContains('pattern');

        $this->client->call('search_exercises', ['query' => '"%"']);
    }

    public function testATooLongSearchIsRefused(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageIsOrContains('longer than');

        $this->client->call('search_exercises', ['query' => str_repeat('curl ', 50)]);
    }

    public function testItOffersTheCoachingPrompts(): void
    {
        $names = array_map(static fn (array $prompt): mixed => $prompt['name'], $this->client->prompts());
        sort($names);

        self::assertSame(['coach_session', 'weekly_review'], $names);
    }

    public function testEveryPromptOnlyNamesToolsThatExist(): void
    {
        $tools = array_values(array_filter(array_column($this->client->tools(), 'name'), is_string(...)));
        $texts = [
            $this->client->prompt('coach_session', ['workout' => 'Pecs']),
            $this->client->prompt('coach_session'),
            $this->client->prompt('weekly_review'),
        ];

        foreach ($texts as $text) {
            preg_match_all('/\b[a-z]+(?:_[a-z]+)+\b/', $text, $mentioned);
            self::assertNotSame([], $mentioned[0]);
            self::assertSame([], array_values(array_diff($mentioned[0], $tools)), 'A prompt names a tool that does not exist.');
        }
    }

    public function testThePromptsCarryTheServersOwnDates(): void
    {
        self::assertMatchesRegularExpression('/from \d{4}-\d{2}-\d{2} to \d{4}-\d{2}-\d{2}/', $this->client->prompt('weekly_review'));
        self::assertStringContainsString('"Pecs"', $this->client->prompt('coach_session', ['workout' => 'Pecs']));
    }

    public function testItCompletesWorkoutNames(): void
    {
        self::assertSame(['Pecs'], $this->client->complete('coach_session', 'workout', 'pe'));
        self::assertSame([], $this->client->complete('coach_session', 'workout', 'zz'));
    }

    private function search(): void
    {
        $found = $this->call('search_exercises', ['query' => 'bench']);

        self::assertCount(1, $this->listOf($found, 'items'));
    }

    private function planAWorkout(): void
    {
        self::assertCount(1, $this->listOf($this->call('list_workouts'), 'workouts'));

        $this->call('create_workout', ['name' => 'Legs']);
        $this->call('plan_exercise', ['workout' => 'Legs', 'exercise' => 'bench-press', 'sets' => 3, 'reps' => 8, 'weight' => 60]);
        $this->call('plan_exercise', ['workout' => 'Legs', 'exercise' => 'plank', 'sets' => 2, 'seconds' => 30]);
        $this->call('move_exercise', ['workout' => 'Legs', 'position' => 2, 'to' => 1]);
        $this->call('change_exercise', ['workout' => 'Legs', 'position' => 1, 'sets' => 4, 'seconds' => 45]);
        $this->call('remove_exercise', ['workout' => 'Legs', 'position' => 2]);
        $this->call('rename_workout', ['workout' => 'Legs', 'name' => 'Leg day']);

        $shown = $this->call('show_workout', ['workout' => 'Leg day']);
        self::assertSame('Leg day', $shown['name']);

        $this->call('duplicate_workout', ['workout' => 'Leg day', 'name' => 'Copy']);
        $this->call('delete_workout', ['workout' => 'Copy']);
    }

    private function runASession(): void
    {
        $this->call('start_session', ['workout' => 'Pecs']);
        self::assertSame('running', $this->call('current_session')['status']);

        $this->call('log_set', ['position' => 1, 'reps' => 8, 'weight' => 60]);
        self::assertSame('paused', $this->call('pause_session')['status']);
        self::assertSame('running', $this->call('resume_session')['status']);

        $finished = $this->call('finish_session', ['durationMinutes' => 45]);
        self::assertSame('finished', $finished['status']);

        $this->call('start_session', ['workout' => 'Pecs']);
        self::assertSame('aborted', $this->call('abort_session')['status']);
    }

    private function correctHistory(): void
    {
        $listed = $this->call('list_sessions', ['limit' => 5, 'workout' => 'pecs', 'status' => 'finished']);
        self::assertSame(1, $listed['matching']);
        $finished = $this->listOf($listed, 'sessions')[0];
        self::assertIsArray($finished);
        $id = $finished['id'];

        $shown = $this->call('show_session', ['sessionId' => $id]);
        self::assertCount(1, $this->listOf($shown, 'sets'));

        $this->call('update_session', ['sessionId' => $id, 'durationMinutes' => 40]);
        $added = $this->call('add_set', ['sessionId' => $id, 'position' => 1, 'reps' => 6, 'weight' => 65]);

        $sets = $this->listOf($added, 'sets');
        $last = end($sets);
        self::assertIsArray($last);
        $setId = $last['id'];

        $this->call('change_set', ['setId' => $setId, 'reps' => 7, 'weight' => 65]);
        $this->call('remove_set', ['setId' => $setId]);

        self::assertSame($id, $this->call('delete_session', ['sessionId' => $id])['deleted']);
    }

    /**
     * @param array<string, mixed> $arguments
     *
     * @return array<string, mixed>
     */
    private function call(string $tool, array $arguments = []): array
    {
        $this->called[] = $tool;
        $answer = $this->client->call($tool, $arguments);

        self::assertFalse($answer['isError'], \sprintf('%s refused: %s', $tool, $answer['text']));

        return $answer['structured'];
    }

    /**
     * @param array<string, mixed> $answer
     *
     * @return list<mixed>
     */
    private function listOf(array $answer, string $key): array
    {
        $list = $answer[$key] ?? null;
        self::assertIsArray($list);
        self::assertTrue(array_is_list($list));

        return $list;
    }
}
