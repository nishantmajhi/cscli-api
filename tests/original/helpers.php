<?php

/**
 * Detects whether the client wants JSON, based on the Accept header.
 * Defaults to plain text - this keeps `curl` and a browser address bar
 * giving readable output, while `curl -H "Accept: application/json"` or
 * any JSON-aware client gets structured output instead.
 */
function wantsJson(): bool {
    $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
    return stripos($accept, 'application/json') !== false;
}

/**
 * Reference text for a human looking at the API directly (browser, curl,
 * docs). This is intentionally NOT reused for error responses - an error
 * should explain what went wrong and what to do next, not just hand back
 * the entire endpoint list with no context.
 */
function showEndpoints() {
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
            "\n" .
            "=============================\n" .
            "          Examples            \n" .
            "=============================\n" .
            "for listing all php programs\n" .
            "  GET /list/php\n" .
            "\n" .
            "for viewing factorial.php\n" .
            "  GET /view/factorial.php\n" .
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
 */
function errorResponse(string $message, ?string $hint = null): array {
    if (wantsJson()) {
        $body = ['error' => $message];
        if ($hint !== null) {
            $body['hint'] = $hint;
        }
        return [json_encode($body) . "\n", 'application/json'];
    }

    $text = $message . "\n";
    if ($hint !== null) {
        $text .= $hint . "\n";
    }
    return [$text, 'text/plain'];
}

/**
 * Builds a successful list response (languages, programs) in plain text
 * or JSON depending on the client's Accept header.
 *
 * @param string $label Human-readable label, e.g. "Supported Languages".
 * @param string $key   JSON key for the list, e.g. "languages".
 * @param list<string> $items
 */
function listResponse(string $label, string $key, array $items): array {
    if (wantsJson()) {
        return [json_encode([$key => $items]) . "\n", 'application/json'];
    }

    $text = $items === []
        ? "$label: (none found)\n"
        : "$label:\n- " . implode("\n- ", $items) . "\n";
    return [$text, 'text/plain'];
}

function listLanguages() {
    $dirs = glob(dirname(__DIR__, 1) . DIRECTORY_SEPARATOR . "src" . DIRECTORY_SEPARATOR . "*", GLOB_ONLYDIR);
    return array_map('basename', $dirs);
}

function listPrograms($lang) {
    $path = dirname(__DIR__, 1) . DIRECTORY_SEPARATOR . "src" . DIRECTORY_SEPARATOR . $lang;
    if (!is_dir($path)) {
        return null;
    }
    $files = glob($path . DIRECTORY_SEPARATOR . "*.*");
    return array_map('basename', $files);
}

function viewProgram($program) {
    $langs = listLanguages();
    foreach ($langs as $lang) {
        $filePath = dirname(__DIR__, 1) . DIRECTORY_SEPARATOR . "src" . DIRECTORY_SEPARATOR . $lang . DIRECTORY_SEPARATOR . $program;
        if (is_file($filePath)) {
            return file_get_contents($filePath);
        }
    }
    return null;
}
