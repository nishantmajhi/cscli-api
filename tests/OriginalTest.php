<?php

/**
 * The regression half.
 *
 * Everything asserted here is asserted about the code as it was found, and
 * every one of these assertions is the inverse of one in CatalogTest or
 * DispatchTest. If a test in this file starts failing it means the original
 * has stopped misbehaving, which can only mean the copy under tests/original/
 * is no longer the original - so the first test checks exactly that against
 * git before any of the others run.
 */

declare(strict_types=1);

require_once __DIR__ . DIRECTORY_SEPARATOR . 'Fixture.php';

/** Copies the preserved original next to a fixture tree and returns that root. */
function originalRoot(): string
{
    static $root = null;

    if ($root !== null) {
        return $root;
    }

    $root = Fixture::root();
    $utils = $root . DIRECTORY_SEPARATOR . 'utils';

    if (!is_dir($utils)) {
        mkdir($utils, 0777, true);
    }

    foreach (['helpers.php', 'Router.php'] as $file) {
        copy(__DIR__ . '/original/' . $file, $utils . DIRECTORY_SEPARATOR . $file);
    }

    return $root;
}

/** @return array{ok: bool, value?: mixed, error?: string, warnings: list<string>} */
function callOriginal(string $function, ?string $argument = null): array
{
    $command = array_map(
        'escapeshellarg',
        array_filter([
            PHP_BINARY,
            __DIR__ . '/original/driver.php',
            originalRoot(),
            $function,
            $argument,
        ], static fn ($part) => $part !== null)
    );

    $output = shell_exec(implode(' ', $command) . ' 2>&1');
    $decoded = json_decode((string) $output, true);

    if (!is_array($decoded)) {
        throw new RuntimeException('driver produced no JSON: ' . var_export($output, true));
    }

    return $decoded;
}

test('the preserved original is byte-identical to the baseline commit', function () {
    $baseline = trim((string) shell_exec(
        'git -C ' . escapeshellarg(dirname(__DIR__)) .
        ' log --format=%H%x09%s --all 2>&1'
    ));

    if ($baseline === '' || str_contains($baseline, 'not a git repository')) {
        echo "  skip  git unavailable, cannot verify tests/original/ against the baseline\n";
        return;
    }

    $commit = null;
    foreach (explode("\n", $baseline) as $line) {
        [$hash, $subject] = array_pad(explode("\t", trim($line), 2), 2, '');
        if (str_starts_with($subject, 'Baseline: working tree as found')) {
            $commit = $hash;
            break;
        }
    }

    if ($commit === null) {
        fail('no commit with the baseline subject was found; tests/original/ cannot be verified');
        return;
    }

    foreach (['helpers.php', 'Router.php'] as $file) {
        $fromGit = shell_exec(
            'git -C ' . escapeshellarg(dirname(__DIR__)) .
            ' show ' . escapeshellarg($commit . ':utils/' . $file)
        );
        $onDisk = file_get_contents(__DIR__ . '/original/' . $file);

        assertSame(
            str_replace("\r\n", "\n", (string) $fromGit),
            str_replace("\r\n", "\n", (string) $onDisk),
            "tests/original/$file still matches utils/$file at the baseline commit"
        );
    }
});

test('ORIGINAL: /list/.. names files outside the source tree', function () {
    $result = callOriginal('listPrograms', '..');

    assertTrue($result['ok'], 'the original returned a value for listPrograms("..")');
    assertSame(
        ['secret.txt'],
        $result['value'],
        'the original leaks the names of files in the project root'
    );
});

test('ORIGINAL: a language name that is not a directory is the only thing refused', function () {
    assertNull(callOriginal('listPrograms', 'nosuchlanguage')['value'], 'unknown language returned null');

    // "." is not in the language list either, but the original accepts it
    // because it only ever asked is_dir().
    assertSame(
        [],
        callOriginal('listPrograms', '.')['value'],
        'the original accepts "." as a language and globs src/ itself'
    );
});

test('ORIGINAL: a percent-encoded language name is not found', function () {
    // This is what the cscli client sends: rawurlencode("c#") is "c%23".
    assertNull(
        callOriginal('listPrograms', 'c%23')['value'],
        'the original 404s on the encoded form of a language that exists'
    );
    assertNull(
        callOriginal('listPrograms', 'c%2B%2B')['value'],
        'the original 404s on the encoded form of c++'
    );

    // And the unencoded forms work, which is what makes it an encoding bug
    // rather than a missing language.
    assertSame(['sharp.cs'], callOriginal('listPrograms', 'c#')['value'], 'c# exists in the fixture');
    assertSame(['plus.cpp'], callOriginal('listPrograms', 'c++')['value'], 'c++ exists in the fixture');
});

test('ORIGINAL: the router does not decode percent-escapes', function () {
    $original = file_get_contents(__DIR__ . '/original/Router.php');

    assertNotContains('urldecode', (string) $original, 'the original Router never decodes a segment');
    assertContains("explode('/', \$this->uri)", (string) $original, 'the original Router splits on raw slashes');
});

test('ORIGINAL: a duplicate program name resolves to whichever directory globs first', function () {
    $result = callOriginal('viewProgram', 'one.txt');

    // alpha and beta both contain one.txt. The original returns the first hit
    // in listLanguages() order and gives the caller no way to ask for the
    // other, and no indication that there was another.
    assertSame("alpha one\n", $result['value'], 'the original silently picks one of the two');
});

test('ORIGINAL: dotfiles are hidden by the glob pattern rather than on purpose', function () {
    $programs = callOriginal('listPrograms', 'alpha')['value'];

    assertSame(['one.txt'], $programs, '.hidden is not listed');

    // It is hidden because glob() skips leading dots, not because anything
    // decided to hide it - the same pattern also hides any file without an
    // extension, which is why the fixed code filters explicitly instead.
    assertContains('"*.*"', file_get_contents(__DIR__ . '/original/helpers.php'), 'the pattern is *.*');
});
