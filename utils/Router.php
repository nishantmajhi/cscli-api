<?php

declare(strict_types=1);

/**
 * Splits a request path into route segments.
 *
 * Two things here are not obvious.
 *
 * The request URI is a constructor argument. It used to be read from
 * $_SERVER directly, which meant the only way to exercise the routing was
 * over a socket.
 *
 * Segments are percent-decoded, which the original did not do. That omission
 * was not a safety measure even though it looked like one - it meant two of
 * the nine languages in src/ could not be reached at all, because the cscli
 * client builds its URLs with rawurlencode() and "c#" and "c++" come out as
 * "c%23" and "c%2B%2B". Decoding is safe here because nothing downstream
 * turns a segment into a path without first finding it in a directory
 * listing; see Catalog. Decoding happens after the split, so a "%2F" in a
 * segment cannot invent a new route - it produces a segment with a slash in
 * it, which matches no language and no program.
 */
final class Router
{
    private string $uri;

    /** @var list<string> */
    private array $segments;

    public function __construct(?string $requestUri = null)
    {
        $requestUri ??= $_SERVER['REQUEST_URI'] ?? '/';

        $path = parse_url($requestUri, PHP_URL_PATH);

        // parse_url() returns false on a URI it cannot make sense of, and
        // null when there is no path component. Neither is a string, and
        // both mean the same thing to this application: no route.
        $this->uri = is_string($path) ? trim($path, '/') : '';

        $this->segments = array_map('rawurldecode', explode('/', $this->uri));
    }

    /**
     * The trimmed path, still percent-encoded.
     *
     * This is what the "unknown endpoint" message echoes back, so it is the
     * encoded form on purpose: showing a user the decoded form of what they
     * typed invites them to retype the decoded form, which is a different
     * request.
     */
    public function getUri(): string
    {
        return $this->uri;
    }

    /** @return list<string> */
    public function getSegments(): array
    {
        return $this->segments;
    }

    /**
     * One decoded segment, or null if the request had no such segment or it
     * was empty.
     *
     * Empty counts as absent so that "/list/" behaves like "/list". The
     * original tested the segment for truthiness, which also treated a
     * segment of "0" as absent - harmless while nothing is called 0, and
     * wrong for a reason nobody would find.
     */
    public function segment(int $index): ?string
    {
        $value = $this->segments[$index] ?? '';

        return $value === '' ? null : $value;
    }
}
