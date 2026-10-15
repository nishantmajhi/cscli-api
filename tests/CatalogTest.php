<?php

declare(strict_types=1);

require_once __DIR__ . DIRECTORY_SEPARATOR . 'Fixture.php';
require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . 'utils' . DIRECTORY_SEPARATOR . 'Catalog.php';

function catalog(): Catalog
{
    return new Catalog(Fixture::src());
}

test('languages are the directories under the root, sorted', function () {
    assertSame(['alpha', 'beta', 'c#', 'c++'], catalog()->languages(), 'every language directory, in order');
});

test('a language that is not in the listing is refused', function () {
    $catalog = catalog();

    assertNull($catalog->programs('nosuchlanguage'), 'an unknown name');
    assertFalse($catalog->hasLanguage('nosuchlanguage'), 'hasLanguage agrees');
});

test('.. is refused rather than globbing the parent directory', function () {
    // This is the bug. src/.. exists and is a directory, so the original
    // answered it; the file outside src/ is there in the fixture precisely
    // so that answering it would be visible.
    assertNull(catalog()->programs('..'), '".." is not a language');

    // Proof the fixture would expose something if it were answered.
    assertTrue(is_file(Fixture::root() . '/secret.txt'), 'the fixture has a file outside src/');
});

test('. and the empty string are refused', function () {
    $catalog = catalog();

    assertNull($catalog->programs('.'), '"." is not a language');
    assertNull($catalog->programs(''), 'the empty string is not a language');
});

test('a segment containing a separator is refused', function () {
    $catalog = catalog();

    // A decoded %2F produces one of these. It matches no language, so it
    // never becomes a path.
    assertNull($catalog->programs('../..'), 'a relative path is not a language');
    assertNull($catalog->programs('alpha/..'), 'a path that starts inside src/ is not a language');
    assertNull($catalog->programs('/etc'), 'an absolute path is not a language');
});

test('a NUL byte is refused without reaching the filesystem', function () {
    // PHP 8 throws a ValueError from any filesystem call given a string with
    // a NUL in it, which would be a 500. The membership test runs first, so
    // it never gets there.
    assertNull(catalog()->programs("alpha\0"), 'a NUL-terminated language name');
    assertNull(catalog()->programs("..\0.png"), 'the classic NUL-truncation payload');
});

test('a language name is matched exactly, not the way the filesystem would', function () {
    $catalog = catalog();

    // On Windows every one of these reaches src/alpha through is_dir(), and
    // on Linux none of them does. Neither is a language.
    assertNull($catalog->programs('ALPHA'), 'a different case');
    assertNull($catalog->programs('alpha '), 'a trailing space');
    assertNull($catalog->programs('alpha.'), 'a trailing dot');
});

test('programs are the files in a language, sorted', function () {
    assertSame(['one.txt'], catalog()->programs('alpha'), 'alpha');
    assertSame(['one.txt', 'two.txt'], catalog()->programs('beta'), 'beta, in order');
});

test('dotfiles are never listed', function () {
    assertTrue(is_file(Fixture::src() . '/alpha/.hidden'), 'the fixture has a dotfile');
    assertNotContains('.hidden', implode(',', catalog()->programs('alpha')), 'and it is not listed');
});

test('a language name that needed encoding is an ordinary language here', function () {
    // Catalog is given the decoded name; the decoding is the Router's job.
    assertSame(['sharp.cs'], catalog()->programs('c#'), 'c#');
    assertSame(['plus.cpp'], catalog()->programs('c++'), 'c++');

    // And the encoded form is not a language, so if the decoding is ever
    // removed again this fails rather than silently 404ing.
    assertNull(catalog()->programs('c%23'), 'the encoded form is not a language');
});

test('reading a program that exists returns its bytes and its language', function () {
    $result = catalog()->read('beta', 'two.txt');

    assertSame('ok', $result['status'], 'status');
    assertSame("beta two\n", $result['contents'], 'contents');
    assertSame('beta', $result['language'], 'language');
});

test('reading refuses a program name that is not in the listing', function () {
    $catalog = catalog();

    assertSame('missing', $catalog->read('alpha', 'two.txt')['status'], 'a file that is in another language');
    assertSame('missing', $catalog->read('alpha', '../secret.txt')['status'], 'a traversal out of src/');
    assertSame('missing', $catalog->read('alpha', '../../secret.txt')['status'], 'a traversal above the root');
    assertSame('missing', $catalog->read('alpha', '.hidden')['status'], 'a dotfile, which is not listed');
    assertSame('missing', $catalog->read('nosuchlanguage', 'one.txt')['status'], 'an unknown language');
});

test('find searches languages in sorted order and says which one answered', function () {
    // alpha/one.txt and beta/one.txt both exist. Alphabetical order decides,
    // and the answer names the winner rather than leaving the caller guessing.
    $result = catalog()->find('one.txt');

    assertSame('ok', $result['status'], 'status');
    assertSame('alpha', $result['language'], 'alpha sorts first');
    assertSame("alpha one\n", $result['contents'], 'and its contents are the ones returned');
});

test('find refuses a name that is not a program in any language', function () {
    $catalog = catalog();

    assertSame('missing', $catalog->find('nosuchfile.txt')['status'], 'an unknown name');
    assertSame('missing', $catalog->find('../secret.txt')['status'], 'a traversal');
    assertSame('missing', $catalog->find('secret.txt')['status'], 'a real file that is not in src/');
});

test('languagesContaining names every language with that program', function () {
    $catalog = catalog();

    assertSame(['alpha', 'beta'], $catalog->languagesContaining('one.txt'), 'the ambiguous name');
    assertSame(['beta'], $catalog->languagesContaining('two.txt'), 'an unambiguous one');
    assertSame([], $catalog->languagesContaining('nosuchfile.txt'), 'a name in no language');
});

test('a root that does not exist reports no languages rather than warning', function () {
    $catalog = new Catalog(Fixture::root() . DIRECTORY_SEPARATOR . 'nosuchdirectory');

    assertSame([], $catalog->languages(), 'no languages');
    assertNull($catalog->programs('alpha'), 'and nothing in them');
});

test('a file larger than the limit is refused rather than read', function () {
    $path = Fixture::src() . DIRECTORY_SEPARATOR . 'beta' . DIRECTORY_SEPARATOR . 'big.txt';
    file_put_contents($path, str_repeat('x', Catalog::MAX_FILE_BYTES + 1));

    try {
        $result = catalog()->read('beta', 'big.txt');

        assertSame('too-large', $result['status'], 'status');
        assertSame(Catalog::MAX_FILE_BYTES + 1, $result['size'], 'the size is reported');
        assertFalse(isset($result['contents']), 'and the bytes were never read');
    } finally {
        unlink($path);
    }
});

test('a file exactly at the limit is still served', function () {
    $path = Fixture::src() . DIRECTORY_SEPARATOR . 'beta' . DIRECTORY_SEPARATOR . 'atlimit.txt';
    file_put_contents($path, str_repeat('x', Catalog::MAX_FILE_BYTES));

    try {
        assertSame('ok', catalog()->read('beta', 'atlimit.txt')['status'], 'the limit is inclusive');
    } finally {
        unlink($path);
    }
});

test('the root is taken as given, with or without a trailing separator', function () {
    $withSlash = new Catalog(Fixture::src() . DIRECTORY_SEPARATOR);

    assertSame(catalog()->languages(), $withSlash->languages(), 'the same languages either way');
});
