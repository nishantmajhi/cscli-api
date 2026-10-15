<?php

declare(strict_types=1);

require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . 'utils' . DIRECTORY_SEPARATOR . 'Router.php';

test('the path is trimmed of slashes and the query string is dropped', function () {
    assertSame('list/php', (new Router('/list/php'))->getUri(), 'a plain path');
    assertSame('list/php', (new Router('/list/php/'))->getUri(), 'a trailing slash');
    assertSame('list/php', (new Router('list/php'))->getUri(), 'no leading slash');
    assertSame('list/php', (new Router('/list/php?x=1'))->getUri(), 'a query string');
    assertSame('list/php', (new Router('http://example.test/list/php'))->getUri(), 'an absolute URI');
});

test('the root path has no uri', function () {
    assertSame('', (new Router('/'))->getUri(), 'a single slash');
    assertSame('', (new Router(''))->getUri(), 'an empty request URI');
    assertSame('', (new Router('/?x=1'))->getUri(), 'a query string on the root');
});

test('a URI that cannot be parsed is treated as the root, not as an error', function () {
    // parse_url() returns false for these. The original passed that false
    // straight into trim(); this asserts the result rather than the route it
    // happens to reach.
    assertSame('', (new Router('//:80'))->getUri(), 'a malformed authority');
    assertSame('', (new Router('http://'))->getUri(), 'a scheme with nothing after it');
});

test('segments are split on the raw slashes', function () {
    assertSame(['list', 'php'], (new Router('/list/php'))->getSegments(), 'two segments');
    assertSame(['view', 'php', 'factorial.php'], (new Router('/view/php/factorial.php'))->getSegments(), 'three');
});

test('segments are percent-decoded', function () {
    // This is what the cscli client sends. Without the decoding, two of the
    // nine languages in src/ cannot be listed at all.
    assertSame(['list', 'c#'], (new Router('/list/c%23'))->getSegments(), 'c%23 is c#');
    assertSame(['list', 'c++'], (new Router('/list/c%2B%2B'))->getSegments(), 'c%2B%2B is c++');
    assertSame(['view', 'a b.txt'], (new Router('/view/a%20b.txt'))->getSegments(), 'a space');
});

test('a plus sign is a plus sign, not a space', function () {
    // rawurldecode(), not urldecode(): "+" means space only in a query
    // string, and src/c++ is a real directory name.
    assertSame(['list', 'c++'], (new Router('/list/c++'))->getSegments(), 'a literal plus');
});

test('a decoded slash cannot create a new segment', function () {
    // Decoding happens after the split, so %2F produces a segment with a
    // slash in it rather than two segments. That segment matches no language
    // and no program, which is where it stops.
    assertSame(['view', '../../index.php'], (new Router('/view/..%2F..%2Findex.php'))->getSegments(), 'one segment');
    assertSame(2, count((new Router('/view/..%2F..%2Findex.php'))->getSegments()), 'still two segments');
});

test('the echoed uri stays encoded', function () {
    // getUri() feeds the "unknown endpoint" message. Showing the decoded
    // form would invite the reader to retype something different from what
    // they sent.
    assertSame('nope/c%23', (new Router('/nope/c%23'))->getUri(), 'still encoded');
});

test('segment() treats an absent or empty segment as absent', function () {
    assertSame('php', (new Router('/list/php'))->segment(1), 'a segment that is there');
    assertNull((new Router('/list'))->segment(1), 'a segment that is not');
    assertNull((new Router('/list/'))->segment(1), 'a trailing slash is not a segment');
    assertNull((new Router('/list//x'))->segment(1), 'an empty segment in the middle');
});

test('segment() does not treat "0" as absent', function () {
    // The original tested the segment for truthiness, so a program or
    // language called "0" reported itself as missing.
    assertSame('0', (new Router('/list/0'))->segment(1), 'zero is a name');
});

test('the request URI defaults to the root when the superglobal is absent', function () {
    $saved = $_SERVER['REQUEST_URI'] ?? null;
    unset($_SERVER['REQUEST_URI']);

    try {
        // Under the CLI there is no REQUEST_URI. Reading it unguarded is a
        // deprecation notice in PHP 8.1+, which lands in the response body.
        assertSame('', (new Router())->getUri(), 'no superglobal, no route');
    } finally {
        if ($saved !== null) {
            $_SERVER['REQUEST_URI'] = $saved;
        }
    }
});

test('the request URI is read from the superglobal when it is there', function () {
    $saved = $_SERVER['REQUEST_URI'] ?? null;
    $_SERVER['REQUEST_URI'] = '/languages';

    try {
        assertSame('languages', (new Router())->getUri(), 'read from $_SERVER');
    } finally {
        if ($saved === null) {
            unset($_SERVER['REQUEST_URI']);
        } else {
            $_SERVER['REQUEST_URI'] = $saved;
        }
    }
});
