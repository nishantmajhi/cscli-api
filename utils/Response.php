<?php

declare(strict_types=1);

/**
 * A response that has been decided but not yet sent.
 *
 * The original built responses through a fluent
 * `$router->response($body, $status)->head('Content-Type', $type)` in which
 * head() both set the header and echoed the body and called exit(). It read
 * well, but it meant every route had to remember to call head() or nothing
 * was sent at all, and it meant the routing could not be tested without a
 * socket: there was no point at which a decision existed as a value.
 *
 * Here the decision is the value. dispatch() returns one of these and
 * index.php sends it, in one place, so there is nothing to forget.
 */
final class Response
{
    /** @param array<string, string> $headers */
    public function __construct(
        public readonly int $status,
        public readonly string $body,
        public readonly string $contentType = 'text/plain; charset=utf-8',
        public readonly array $headers = [],
    ) {
    }

    /** The same response with extra headers merged in. */
    public function withHeaders(array $headers): self
    {
        return new self($this->status, $this->body, $this->contentType, $this->headers + $headers);
    }

    /** @return array<string, string> Content-Type included, ready to send. */
    public function allHeaders(): array
    {
        return ['Content-Type' => $this->contentType] + $this->headers;
    }

    public function send(): void
    {
        http_response_code($this->status);

        foreach ($this->allHeaders() as $name => $value) {
            header("$name: $value");
        }

        // 304 is defined as having no body; every other status here does.
        if ($this->status !== 304) {
            echo $this->body;
        }
    }
}
