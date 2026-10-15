<?php

/**
 * Runs one call against the *original* helpers and prints the result as JSON.
 *
 * This exists as a separate process because the original code declares
 * `listLanguages()`, `listPrograms()` and `viewProgram()` as global functions
 * with the same names the fixed code uses, so the two cannot be loaded into
 * one PHP process. The suite copies `helpers.php` next to a fixture tree and
 * calls this script once per assertion.
 *
 * Usage: php driver.php <root> <function> [argument]
 *
 * <root> is a directory containing utils/helpers.php and src/, which is what
 * `dirname(__DIR__, 1)` inside the original resolves to.
 */

declare(strict_types=1);

[$self, $root, $function] = [$argv[0], $argv[1] ?? '', $argv[2] ?? ''];
$argument = $argv[3] ?? null;

require_once $root . DIRECTORY_SEPARATOR . 'utils' . DIRECTORY_SEPARATOR . 'helpers.php';

// Warnings from the original's unguarded filesystem calls are part of what is
// being measured - the review's point was that they land in the response body -
// so they are captured rather than silenced.
$warnings = [];
set_error_handler(static function (int $no, string $message) use (&$warnings): bool {
    $warnings[] = $message;
    return true;
});

try {
    $value = $argument === null ? $function() : $function($argument);
    $result = ['ok' => true, 'value' => $value, 'warnings' => $warnings];
} catch (Throwable $e) {
    $result = ['ok' => false, 'error' => $e::class . ': ' . $e->getMessage(), 'warnings' => $warnings];
}

echo json_encode($result, JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE), "\n";
