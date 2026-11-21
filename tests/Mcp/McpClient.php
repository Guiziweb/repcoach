<?php

declare(strict_types=1);

namespace App\Tests\Mcp;

use RuntimeException;
use stdClass;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

final class McpClient
{
    private int $nextId = 1;

    private ?string $sessionId = null;

    public function __construct(
        private readonly KernelBrowser $browser,
    ) {
    }

    public function connect(): void
    {
        $this->request('initialize', [
            'protocolVersion' => '2025-06-18',
            'capabilities' => new stdClass(),
            'clientInfo' => ['name' => 'phpunit', 'version' => '1.0.0'],
        ]);

        $this->sessionId = $this->browser->getResponse()->headers->get('Mcp-Session-Id');
        self::assertSessionOpened($this->sessionId);

        $this->notify('notifications/initialized');
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function tools(): array
    {
        $result = $this->request('tools/list');
        $tools = $result['tools'] ?? null;
        self::assertList($tools);

        return $tools;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function prompts(): array
    {
        $prompts = $this->request('prompts/list')['prompts'] ?? null;
        self::assertList($prompts);

        return $prompts;
    }

    /**
     * @param array<string, string> $arguments
     */
    public function prompt(string $name, array $arguments = []): string
    {
        $messages = $this->request('prompts/get', ['name' => $name, 'arguments' => (object) $arguments])['messages'] ?? null;
        $first = \is_array($messages) ? ($messages[0] ?? null) : null;
        $content = \is_array($first) ? ($first['content'] ?? null) : null;

        return \is_array($content) && \is_string($content['text'] ?? null) ? $content['text'] : throw new RuntimeException(\sprintf('Prompt "%s" returned no text.', $name));
    }

    /**
     * @return list<string>
     */
    public function complete(string $prompt, string $argument, string $value): array
    {
        $completion = $this->request('completion/complete', [
            'ref' => ['type' => 'ref/prompt', 'name' => $prompt],
            'argument' => ['name' => $argument, 'value' => $value],
        ])['completion'] ?? null;
        $values = \is_array($completion) ? ($completion['values'] ?? null) : null;

        return \is_array($values) ? array_values(array_filter($values, \is_string(...))) : [];
    }

    /**
     * @param array<string, mixed> $arguments
     *
     * @return array{isError: bool, structured: array<string, mixed>, text: string}
     */
    public function call(string $tool, array $arguments = []): array
    {
        $result = $this->request('tools/call', ['name' => $tool, 'arguments' => (object) $arguments]);
        $structured = $result['structuredContent'] ?? [];
        $content = $result['content'] ?? [];

        return [
            'isError' => true === ($result['isError'] ?? false),
            'structured' => self::object($structured),
            'text' => \is_array($content) && \is_array($content[0] ?? null) && \is_string($content[0]['text'] ?? null) ? $content[0]['text'] : '',
        ];
    }

    /**
     * @param array<string, mixed> $params
     *
     * @return array<string, mixed>
     */
    private function request(string $method, array $params = []): array
    {
        $this->send(['jsonrpc' => '2.0', 'id' => $this->nextId++, 'method' => $method, 'params' => (object) $params]);

        $decoded = json_decode($this->browser->getResponse()->getContent() ?: '{}', true, flags: \JSON_THROW_ON_ERROR);
        \assert(\is_array($decoded));
        $result = $decoded['result'] ?? null;
        if (!\is_array($result)) {
            throw new RuntimeException(\sprintf('"%s" got no result: %s', $method, json_encode($decoded)));
        }

        return self::object($result);
    }

    private function notify(string $method): void
    {
        $this->send(['jsonrpc' => '2.0', 'method' => $method]);
    }

    /**
     * @param array<string, mixed> $message
     */
    private function send(array $message): void
    {
        $server = ['CONTENT_TYPE' => 'application/json', 'HTTP_ACCEPT' => 'application/json, text/event-stream'];
        if (null !== $this->sessionId) {
            $server['HTTP_MCP_SESSION_ID'] = $this->sessionId;
        }

        $this->browser->request('POST', '/mcp', server: $server, content: json_encode($message, \JSON_THROW_ON_ERROR));
    }

    /**
     * @return array<string, mixed>
     */
    private static function object(mixed $value): array
    {
        return \is_array($value) ? array_filter($value, \is_string(...), \ARRAY_FILTER_USE_KEY) : [];
    }

    private static function assertSessionOpened(?string $sessionId): void
    {
        if (null === $sessionId || '' === $sessionId) {
            throw new RuntimeException('The server did not open an MCP session.');
        }
    }

    /**
     * @phpstan-assert list<array<string, mixed>> $tools
     */
    private static function assertList(mixed $tools): void
    {
        if (!\is_array($tools) || !array_is_list($tools)) {
            throw new RuntimeException('tools/list returned no list of tools.');
        }
    }
}
