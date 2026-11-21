<?php

declare(strict_types=1);

namespace App\Exercise\Import;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final readonly class RepDbDataset
{
    public function __construct(
        private HttpClientInterface $client,
        #[Autowire('%app.repdb_url%')]
        private string $url,
    ) {
    }

    /**
     * @return iterable<array<mixed, mixed>>
     */
    public function rows(): iterable
    {
        try {
            $decoded = $this->client->request('GET', $this->url, ['timeout' => 30])->toArray();
        } catch (ExceptionInterface $failure) {
            throw new DatasetUnavailable(\sprintf('Cannot load the dataset from %s: %s', $this->url, $failure->getMessage()), previous: $failure);
        }

        $rows = $decoded['exercises'] ?? null;

        if (!\is_array($rows)) {
            throw new DatasetUnavailable(\sprintf('%s has no "exercises" list.', $this->url));
        }

        foreach ($rows as $row) {
            if (\is_array($row)) {
                yield $row;
            }
        }
    }
}
