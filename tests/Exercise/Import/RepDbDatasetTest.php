<?php

declare(strict_types=1);

namespace App\Tests\Exercise\Import;

use App\Exercise\Import\DatasetUnavailable;
use App\Exercise\Import\RepDbDataset;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\Exception\TransportException;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

#[CoversClass(RepDbDataset::class)]
final class RepDbDatasetTest extends TestCase
{
    private const string URL = 'https://dataset.test/exercises.json';

    public function testItYieldsEveryExerciseRow(): void
    {
        $rows = iterator_to_array($this->dataset($this->json(['count' => 2, 'exercises' => [['id' => 'a'], ['id' => 'b']]]))->rows(), false);

        self::assertCount(2, $rows);
        self::assertSame('a', $rows[0]['id']);
    }

    public function testItSkipsRowsThatAreNotObjects(): void
    {
        $dataset = $this->dataset($this->json(['exercises' => [['id' => 'a'], 'oops', 42]]));

        self::assertCount(1, iterator_to_array($dataset->rows(), false));
    }

    public function testItAsksForTheConfiguredAddress(): void
    {
        $requested = null;
        $client = new MockHttpClient(function (string $method, string $url) use (&$requested): MockResponse {
            $requested = [$method, $url];

            return $this->json(['exercises' => []]);
        });

        iterator_to_array((new RepDbDataset($client, self::URL))->rows());

        self::assertSame(['GET', self::URL], $requested);
    }

    public function testItComplainsWhenTheServerRefuses(): void
    {
        $this->expectException(DatasetUnavailable::class);
        $this->expectExceptionMessageIsOrContains('Cannot load the dataset');

        iterator_to_array($this->dataset(new MockResponse('', ['http_code' => 404]))->rows());
    }

    public function testItComplainsWhenTheNetworkIsDown(): void
    {
        $client = new MockHttpClient(static fn (): MockResponse => throw new TransportException('Could not resolve host'));

        $this->expectException(DatasetUnavailable::class);
        $this->expectExceptionMessageIsOrContains('Could not resolve host');

        iterator_to_array((new RepDbDataset($client, self::URL))->rows());
    }

    public function testItComplainsWhenTheExerciseListIsMissing(): void
    {
        $this->expectException(DatasetUnavailable::class);
        $this->expectExceptionMessageIsOrContains('no "exercises" list');

        iterator_to_array($this->dataset($this->json(['count' => 0]))->rows());
    }

    public function testItComplainsWhenTheAnswerIsNotJson(): void
    {
        $this->expectException(DatasetUnavailable::class);
        $this->expectExceptionMessageIsOrContains('Cannot load the dataset');

        iterator_to_array($this->dataset(new MockResponse('not json at all'))->rows());
    }

    private function dataset(MockResponse $response): RepDbDataset
    {
        return new RepDbDataset(new MockHttpClient($response), self::URL);
    }

    /**
     * @param array<string, mixed> $contents
     */
    private function json(array $contents): MockResponse
    {
        return new MockResponse(json_encode($contents, \JSON_THROW_ON_ERROR), ['response_headers' => ['content-type' => 'application/json']]);
    }
}
