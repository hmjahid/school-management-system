<?php

namespace Tests\Unit\Services\CloudBackup;

use App\Services\CloudBackup\Contracts\CloudHttpClient;

/**
 * Records every outgoing request and replays a queued response, so driver tests
 * assert on the exact HTTP calls without touching the network.
 */
class FakeCloudHttpClient implements CloudHttpClient
{
    /** @var array<int, array{method: string, url: string, headers: array<string, string>, body: string|array|null}> */
    public array $requests = [];

    /** @var array<int, array{status?: int, body?: string, headers?: array<string, string>, error?: string|null}> */
    private array $queue = [];

    /**
     * @param  array<int, array<string, mixed>>  $responses
     */
    public function queue(array $responses): static
    {
        foreach ($responses as $response) {
            $this->queue[] = $response;
        }

        return $this;
    }

    public function lastRequest(): ?array
    {
        return $this->requests === [] ? null : $this->requests[count($this->requests) - 1];
    }

    public function request(string $method, string $url, array $headers = [], string|array|null $body = null): array
    {
        $this->requests[] = compact('method', 'url', 'headers', 'body');

        $next = array_shift($this->queue) ?? ['status' => 200, 'body' => '{}'];

        return [
            'status' => (int) ($next['status'] ?? 200),
            'body' => (string) ($next['body'] ?? ''),
            'headers' => (array) ($next['headers'] ?? []),
            'error' => $next['error'] ?? null,
        ];
    }
}
