<?php

declare(strict_types=1);

/**
 * Turning answers into bodies.
 *
 * Nothing in this file reads a superglobal, touches the filesystem or sends
 * anything. It takes values and returns [body, content-type] pairs, which is
 * what lets the route table in dispatch.php be tested without a server.
 */

/**
 * Detects whether the client wants JSON, based on the Accept header.
 *
 * Defaults to plain text - this keeps `curl` and a browser address bar
 * giving readable output, while `curl -H "Accept: application/json"` or
 * any JSON-aware client gets structured output instead.
 *
 * The header is a parameter with a fallback rather than a read of $_SERVER,
 * so that a test can ask for either shape without rewriting a superglobal.
 */
function wantsJson(?string $accept = null): bool
{
    $accept ??= $_SERVER['HTTP_ACCEPT'] ?? '';

    return stripos($accept, 'application/json') !== false;
}

/**
 * Reference text for a human looking at the API directly (browser, curl,
 * docs). This is intentionally NOT reused for error responses - an error
 * should explain what went wrong and what to do next, not just hand back
 * the entire endpoint list with no context.
 */
function showEndpoints(): string
{
    return  "=============================\n" .
            "      CSCLI API Reference     \n" .
            "=============================\n" .
            "A source code retrieval API for CLI tools.\n" .
            "\n" .
            "list the supported languages\n" .
            "  GET /languages\n" .
            "\n" .
            "list the available programs\n" .
            "  GET /list/{language_name}\n" .
            "\n" .
            "view source code of a program\n" .
            "  GET /view/{program_name}\n" .
            "  GET /view/{language_name}/{program_name}\n" .
            "\n" .
            "=============================\n" .
            "          Examples            \n" .
            "=============================\n" .
            "for listing all php programs\n" .
            "  GET /list/php\n" .
            "\n" .
            "for viewing factorial.php\n" .
            "  GET /view/factorial.php\n" .
            "  GET /view/php/factorial.php\n" .
            "\n" .
            "A program name on its own is searched for in every language, in\n" .
            "alphabetical order, and the first match wins. Name the language\n" .
            "too when two languages have a file with the same name. Either way\n" .
            "the answer carries an X-Cscli-Language header saying which one it\n" .
            "came from.\n" .
            "\n" .
            "JSON responses are available on any endpoint by sending\n" .
            "  Accept: application/json\n";
}

/**
 * Builds an error response in plain text or JSON depending on the client's
 * Accept header. Every error names what went wrong, and - where there's an
 * obvious next step - says what to try instead, rather than dumping the
 * full endpoint reference unconditionally.
 *
 * @param string      $message Human-readable explanation of what went wrong.
 * @param string|null $hint    Optional one-line suggestion for what to do next.
 * @return array{0: string, 1: string}
 */
function errorResponse(string $message, ?string $hint = null, bool $json = false): array
{
    if ($json) {
        $body = ['error' => $message];
        if ($hint !== null) {
            $body['hint'] = $hint;
        }
        return [json_encode($body, JSON_INVALID_UTF8_SUBSTITUTE) . "\n", 'application/json'];
    }

    $text = $message . "\n";
    if ($hint !== null) {
        $text .= $hint . "\n";
    }
    return [$text, 'text/plain; charset=utf-8'];
}

/**
 * Builds a successful list response (languages, programs) in plain text
 * or JSON depending on the client's Accept header.
 *
 * @param string $label Human-readable label, e.g. "Supported Languages".
 * @param string $key   JSON key for the list, e.g. "languages".
 * @param list<string> $items
 * @return array{0: string, 1: string}
 */
function listResponse(string $label, string $key, array $items, bool $json = false): array
{
    if ($json) {
        return [json_encode([$key => $items], JSON_INVALID_UTF8_SUBSTITUTE) . "\n", 'application/json'];
    }

    $text = $items === []
        ? "$label: (none found)\n"
        : "$label:\n- " . implode("\n- ", $items) . "\n";

    return [$text, 'text/plain; charset=utf-8'];
}
