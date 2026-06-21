<?php

declare(strict_types=1);

namespace Pictomancer\WP\Tests;

use Pictomancer\Response;
use Pictomancer\Transport;

final class RecordingTransport implements Transport
{
    /** @var list<array<string, mixed>> */
    public array $calls = [];

    public function __construct(private readonly Response $response)
    {
    }

    public function send(string $method, string $url, array $headers, ?string $body, float $timeout): Response
    {
        $this->calls[] = compact('method', 'url', 'headers', 'body', 'timeout');

        return $this->response;
    }

    /** @return array<string, mixed> */
    public function lastBody(): array
    {
        $body = $this->calls[count($this->calls) - 1]['body'];

        return json_decode($body, true, 512, JSON_THROW_ON_ERROR);
    }
}
